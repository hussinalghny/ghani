<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CohortRegistration;
use App\Models\Enrollment;
use App\Models\PaymentOrder;
use App\Models\Voucher;
use App\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** تقرير المبيعات: شكد بعنا، منين، ومن أي محفظة */
class ReportController extends Controller
{
    /** توقيت التقرير (نفس توقيت التطبيق) */
    private const TZ = 'Asia/Baghdad';

    private function range(Request $request): array
    {
        $tz = self::TZ;
        $from = rescue(fn () => CarbonImmutable::parse($request->query('from'), $tz)->startOfDay(), null, false) ?? CarbonImmutable::now($tz)->startOfMonth();
        $to = rescue(fn () => CarbonImmutable::parse($request->query('to'), $tz)->endOfDay(), null, false) ?? CarbonImmutable::now($tz)->endOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->subDays(366)->startOfDay();
        }

        return [$from, $to];
    }

    /** سطور المبيعات: كل اشتراك مدفوع وكل مقعد مؤكد */
    private function lines(CarbonImmutable $from, CarbonImmutable $to)
    {
        $courses = Enrollment::with(['user:id,name,phone', 'course:id,title', 'discountCode:id,code'])
            ->whereBetween('created_at', [$from, $to])->where('price_paid', '>', 0)->get()
            ->map(fn ($e) => [
                'at' => $e->created_at, 'kind' => 'كورس', 'item' => $e->course?->title ?? '—', 'item_key' => 'c'.$e->course_id,
                'name' => $e->user?->name, 'phone' => $e->user?->phone, 'amount' => (int) $e->price_paid,
                'code' => $e->discountCode?->code,
            ]);
        $seats = CohortRegistration::with(['cohort:id,title,price', 'discount:id,code'])
            ->where('status', 'paid')->whereBetween('paid_at', [$from, $to])->get()
            ->map(fn ($r) => [
                'at' => $r->paid_at, 'kind' => 'حضوري', 'item' => $r->cohort?->title ?? '—', 'item_key' => 'h'.$r->cohort_id,
                'name' => $r->name, 'phone' => $r->phone, 'amount' => $r->finalPrice(),
                'code' => $r->discount?->code,
            ]);

        return $courses->concat($seats)->sortByDesc('at')->values();
    }

    public function sales(Request $request)
    {
        [$from, $to] = $this->range($request);
        $lines = $this->lines($from, $to);

        $orders = PaymentOrder::where('status', 'paid')->whereBetween('paid_at', [$from, $to])->get(['method', 'amount', 'paid_by']);
        $methods = PaymentService::methods();
        $byMethod = $orders->groupBy('method')->map(fn ($g, $m) => [
            'label' => $methods[$m]['label'] ?? $m, 'count' => $g->count(), 'sum' => (int) $g->sum('amount'),
            'auto' => $g->where('paid_by', 'auto')->count(),
        ])->sortByDesc('sum')->values();

        $vouchers = Voucher::where('status', 'redeemed')->whereBetween('redeemed_at', [$from, $to]);
        $stats = [
            'sales' => (int) $lines->sum('amount'),
            'count' => $lines->count(),
            'courses' => (int) $lines->where('kind', 'كورس')->sum('amount'),
            'seats' => (int) $lines->where('kind', 'حضوري')->sum('amount'),
            'received' => (int) $orders->sum('amount'),
            'vouchers' => (int) (clone $vouchers)->sum('amount'),
            'vouchers_count' => (clone $vouchers)->count(),
        ];

        $byItem = $lines->groupBy('item_key')->map(fn ($g) => [
            'kind' => $g->first()['kind'], 'item' => $g->first()['item'], 'count' => $g->count(), 'sum' => (int) $g->sum('amount'),
        ])->sortByDesc('sum')->values();

        $byCode = $lines->whereNotNull('code')->groupBy('code')->map(fn ($g, $c) => [
            'code' => $c, 'count' => $g->count(), 'sum' => (int) $g->sum('amount'),
        ])->sortByDesc('sum')->values();

        // مبيعات كل يوم (أقصى 62 عمود حتى يبقى الرسم مقروء)
        $tz = self::TZ;
        $perDay = $lines->groupBy(fn ($l) => $l['at']->copy()->setTimezone($tz)->toDateString())->map(fn ($g) => (int) $g->sum('amount'));
        $days = collect();
        for ($d = $from->max($to->subDays(61)->startOfDay()); $d->lte($to); $d = $d->addDay()) {
            $days->push(['d' => $d->toDateString(), 'v' => $perDay[$d->toDateString()] ?? 0]);
        }

        return view('admin.reports.sales', [
            'from' => $from, 'to' => $to, 'stats' => $stats, 'byMethod' => $byMethod, 'byItem' => $byItem,
            'byCode' => $byCode, 'days' => $days, 'recent' => $lines->take(25),
        ]);
    }

    public function csv(Request $request)
    {
        [$from, $to] = $this->range($request);
        $lines = $this->lines($from, $to);
        $tz = self::TZ;

        return response()->streamDownload(function () use ($lines, $tz) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['التاريخ', 'النوع', 'الكورس', 'الاسم', 'الهاتف', 'المبلغ', 'كود الخصم']);
            foreach ($lines as $l) {
                fputcsv($out, [$l['at']->copy()->setTimezone($tz)->format('Y-m-d H:i'), $l['kind'], $l['item'], $l['name'], "\u{200E}".$l['phone'], $l['amount'], $l['code']]);
            }
            fclose($out);
        }, 'sales-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
