<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\CohortRegistration;
use App\Models\Course;
use App\Services\CohortService;
use Illuminate\Http\Request;

/** إدارة الكورسات الحضورية: التفاصيل، المسجلين، التأكيد اليدوي، الحضور، والتصدير */
class CohortController extends Controller
{
    public function index()
    {
        $cohorts = Cohort::withCount([
            'registrations as paid_count' => fn ($q) => $q->where('status', 'paid'),
            'registrations as pending_count' => fn ($q) => $q->where('status', 'pending'),
            'registrations as waitlist_count' => fn ($q) => $q->where('status', 'waitlist'),
        ])->latest()->get();

        return view('admin.cohorts.index', compact('cohorts'));
    }

    public function show(Request $request, Cohort $cohort)
    {
        $status = $request->query('status', 'all');
        $q = $request->query('q');
        $regs = $cohort->registrations()->with(['user:id,name,phone,balance', 'discount', 'cohort'])->withCount(['attendances', 'attendances as today_count' => fn ($q) => $q->where('day', now('Asia/Baghdad')->toDateString())])
            ->when($status !== 'all', fn ($w) => $w->where('status', $status))
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('code', 'like', "%$q%")))
            ->orderByRaw("case status when 'paid' then 0 when 'pending' then 1 when 'waitlist' then 2 else 3 end")->latest()->paginate(60)->withQueryString();
        $stats = [
            'paid' => $cohort->paidCount(),
            'pending' => $cohort->registrations()->where('status', 'pending')->count(),
            'waitlist' => $cohort->registrations()->where('status', 'waitlist')->count(),
            'checked' => $cohort->registrations()->whereNotNull('checked_in_at')->count(),
            'revenue' => $cohort->paidCount() * $cohort->price,
        ];
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $discounts = \App\Models\DiscountCode::where(fn ($q) => $q->whereNull('cohort_id')->orWhere('cohort_id', $cohort->id))->latest()->get();
        $stats['revenue'] = (int) $cohort->registrations()->where('status', 'paid')->get()->sum(fn ($r) => $r->finalPrice());

        return view('admin.cohorts.show', compact('cohort', 'regs', 'stats', 'status', 'courses', 'discounts'));
    }

    public function update(Request $request, Cohort $cohort)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'price' => ['required', 'integer', 'min:0'],
            'seats' => ['required', 'integer', 'min:1', 'max:1000'],
            'starts_on' => ['nullable', 'date'],
            'schedule' => ['nullable', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:200'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'image' => ['nullable', 'image', 'max:4096'],
            'gallery.*' => ['nullable', 'image', 'max:4096'],
            'logos.*' => ['nullable', 'image', 'max:2048'],
        ]);
        $details = $cohort->details ?? [];
        foreach (['outcomes', 'weeks', 'audience', 'includes', 'partners', 'faq'] as $k) {
            $details[$k] = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $request->input($k)))));
        }
        $details['instructor'] = trim((string) $request->input('instructor_note')) ?: null;
        foreach ($request->file('gallery', []) as $f) {
            $details['gallery'][] = $f->store('cohorts', 'public');
        }
        if ($request->boolean('clear_gallery')) {
            $details['gallery'] = [];
        }
        foreach ($request->file('logos', []) as $i => $f) {
            $details['logos'][$i] = $f->store('cohorts/logos', 'public');
        }
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cohorts', 'public');
        } else {
            unset($data['image']);
        }
        unset($data['gallery'], $data['logos']);
        $cohort->update($data + ['details' => $details, 'is_open' => $request->boolean('is_open')]);

        return back()->with('success', 'تم الحفظ.');
    }

    public function storeDiscount(Request $request, Cohort $cohort)
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/', 'unique:discount_codes,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'integer', 'min:1', $request->input('type') === 'percent' ? 'max:100' : 'max:100000000'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:120'],
        ], ['code.regex' => 'الكود حروف إنكليزية وأرقام فقط.'], ['code' => 'الكود', 'value' => 'قيمة الخصم']);
        \App\Models\DiscountCode::create($data + ['cohort_id' => $request->boolean('all') ? null : $cohort->id]);

        return back()->with('success', 'انضاف كود الخصم '.$data['code'].'.');
    }

    public function toggleDiscount(\App\Models\DiscountCode $discount)
    {
        $discount->update(['is_active' => ! $discount->is_active]);

        return back()->with('success', $discount->is_active ? 'الكود مفعل.' : 'الكود متوقف.');
    }

    public function act(Request $request, CohortRegistration $registration, string $action, CohortService $svc)
    {
        match ($action) {
            'paid' => $svc->markPaid($registration),
            'cancel' => $registration->update(['status' => 'cancelled']),
            'pending' => $registration->update(['status' => 'pending', 'created_at' => now()]),
            'checkin' => $this->toggleToday($registration, $request->user()->id),
        };

        return back()->with('success', 'تم.');
    }

    /** تسجيل/إلغاء حضور اليوم يدويا */
    private function toggleToday(CohortRegistration $r, int $by): void
    {
        $day = now('Asia/Baghdad')->toDateString();
        $a = \App\Models\CohortAttendance::where('registration_id', $r->id)->where('day', $day)->first();
        if ($a) {
            $a->delete();

            return;
        }
        \App\Models\CohortAttendance::create(['registration_id' => $r->id, 'day' => $day, 'scanned_at' => now(), 'scanned_by' => $by]);
        if (! $r->checked_in_at) {
            $r->update(['checked_in_at' => now()]);
        }
    }

    public function csv(Cohort $cohort)
    {
        $rows = $cohort->registrations()->with('discount', 'cohort')->withCount('attendances')->orderBy('status')->orderBy('created_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الرمز', 'الاسم', 'الهاتف', 'الإيميل', 'العمل/الدراسة', 'المستوى', 'الحالة', 'كود الخصم', 'المبلغ', 'تاريخ التسجيل', 'تاريخ الدفع', 'أيام الحضور', 'ملاحظات']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->code, $r->name, $r->phone, $r->email, $r->job, CohortRegistration::LEVELS[$r->level] ?? $r->level,
                    CohortRegistration::STATUSES[$r->status] ?? $r->status, $r->discount?->code, $r->finalPrice(), $r->created_at?->format('Y-m-d H:i'), $r->paid_at?->format('Y-m-d H:i'),
                    $r->attendances_count, $r->notes]);
            }
            fclose($out);
        }, 'registrations-'.$cohort->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
