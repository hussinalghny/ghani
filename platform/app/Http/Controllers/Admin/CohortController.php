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
        $regs = $cohort->registrations()->with('user:id,name,phone,balance')
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

        return view('admin.cohorts.show', compact('cohort', 'regs', 'stats', 'status', 'courses'));
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

    public function act(Request $request, CohortRegistration $registration, string $action, CohortService $svc)
    {
        match ($action) {
            'paid' => $svc->markPaid($registration),
            'cancel' => $registration->update(['status' => 'cancelled']),
            'pending' => $registration->update(['status' => 'pending', 'created_at' => now()]),
            'checkin' => $registration->update(['checked_in_at' => $registration->checked_in_at ? null : now()]),
        };

        return back()->with('success', 'تم.');
    }

    public function csv(Cohort $cohort)
    {
        $rows = $cohort->registrations()->orderBy('status')->orderBy('created_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الرمز', 'الاسم', 'الهاتف', 'الإيميل', 'العمل/الدراسة', 'المستوى', 'الحالة', 'تاريخ التسجيل', 'تاريخ الدفع', 'الحضور', 'ملاحظات']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->code, $r->name, $r->phone, $r->email, $r->job, CohortRegistration::LEVELS[$r->level] ?? $r->level,
                    CohortRegistration::STATUSES[$r->status] ?? $r->status, $r->created_at?->format('Y-m-d H:i'), $r->paid_at?->format('Y-m-d H:i'),
                    $r->checked_in_at ? 'حضر' : '', $r->notes]);
            }
            fclose($out);
        }, 'registrations-'.$cohort->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
