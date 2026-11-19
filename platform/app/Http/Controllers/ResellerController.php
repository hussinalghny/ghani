<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\VoucherService;
use Illuminate\Http\Request;

/** بوابة الموزعين: مكتبات ومحلات تشتري قسائم بخصم وتبيعها للطلاب */
class ResellerController extends Controller
{
    public static function denominations(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) setting('voucher_denominations')))));
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $batches = VoucherBatch::query()->where('reseller_id', $user->id)
            ->withCount([
                'vouchers',
                'vouchers as redeemed_count' => fn ($q) => $q->where('status', 'redeemed'),
                'vouchers as sold_count' => fn ($q) => $q->whereNotNull('sold_at'),
            ])->latest()->paginate(15);

        $stats = [
            'available' => Voucher::where('reseller_id', $user->id)->where('status', 'active')->whereNull('sold_at')->count(),
            'sold' => Voucher::where('reseller_id', $user->id)->whereNotNull('sold_at')->count(),
            'redeemed' => Voucher::where('reseller_id', $user->id)->where('status', 'redeemed')->count(),
        ];
        $denominations = self::denominations();

        return view('reseller.index', compact('user', 'batches', 'stats', 'denominations'));
    }

    public function issue(Request $request, VoucherService $vouchers)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'in:'.implode(',', self::denominations())],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
        ], [], ['amount' => 'الفئة', 'quantity' => 'العدد']);

        $user = $request->user();
        $batch = $vouchers->issueBatch($data, $user, $user, true);

        return redirect()->route('reseller.batch', $batch)
            ->with('success', 'تم إصدار '.$batch->quantity.' قسيمة. خصمنا '.iqd($batch->cost).' من رصيدك.');
    }

    private function own(Request $request, VoucherBatch $batch): void
    {
        abort_unless($batch->reseller_id === $request->user()->id, 404);
    }

    public function batch(Request $request, VoucherBatch $batch)
    {
        $this->own($request, $batch);
        $vouchers = $batch->vouchers()->with('redeemer:id,name')->orderBy('id')->paginate(50);

        return view('reseller.batch', compact('batch', 'vouchers'));
    }

    public function print(Request $request, VoucherBatch $batch)
    {
        $this->own($request, $batch);
        $vouchers = $batch->vouchers()->where('status', 'active')->orderBy('id')->get();

        return view('partials.voucher-print', compact('batch', 'vouchers'));
    }

    public function csv(Request $request, VoucherBatch $batch)
    {
        $this->own($request, $batch);

        return self::exportCsv($batch);
    }

    public function markSold(Request $request, Voucher $voucher)
    {
        abort_unless($voucher->reseller_id === $request->user()->id, 404);
        $voucher->forceFill(['sold_at' => $voucher->sold_at ? null : now()])->save();

        return back();
    }

    public static function exportCsv(VoucherBatch $batch)
    {
        $name = 'vouchers-'.$batch->id.'.csv';

        return response()->streamDownload(function () use ($batch) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['الكود', 'القيمة', 'الحالة', 'تاريخ البيع', 'تاريخ الاستخدام']);
            $batch->vouchers()->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $v) {
                    fputcsv($out, [$v->formatted(), $v->amount, $v->statusLabel(), $v->sold_at?->format('Y-m-d'), $v->redeemed_at?->format('Y-m-d H:i')]);
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
