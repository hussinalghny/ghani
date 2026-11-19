<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResellerController;
use App\Models\Course;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $batches = VoucherBatch::with(['reseller:id,name', 'course:id,title'])
            ->withCount(['vouchers', 'vouchers as redeemed_count' => fn ($q) => $q->where('status', 'redeemed')])
            ->latest()->paginate(20);

        $stats = [
            'active' => Voucher::where('status', 'active')->count(),
            'active_value' => (int) Voucher::where('status', 'active')->sum('amount'),
            'redeemed' => Voucher::where('status', 'redeemed')->count(),
            'redeemed_value' => (int) Voucher::where('status', 'redeemed')->sum('amount'),
        ];

        return view('admin.vouchers.index', compact('batches', 'stats'));
    }

    public function create()
    {
        return view('admin.vouchers.create', [
            'resellers' => User::where('role', 'reseller')->orderBy('name')->get(['id', 'name', 'phone', 'balance', 'reseller_discount']),
            'courses' => Course::orderBy('title')->get(['id', 'title', 'price', 'sale_price']),
            'denominations' => ResellerController::denominations(),
        ]);
    }

    public function store(Request $request, VoucherService $vouchers)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'kind' => ['required', 'in:balance,course'],
            'amount' => ['required_if:kind,balance', 'nullable', 'integer', 'min:250'],
            'course_id' => ['required_if:kind,course', 'nullable', 'exists:courses,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2000'],
            'reseller_id' => ['nullable', 'exists:users,id'],
            'charge_reseller' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ], [], ['amount' => 'القيمة', 'quantity' => 'العدد', 'course_id' => 'الكورس']);

        if ($data['kind'] === 'balance') {
            $data['course_id'] = null;
        } else {
            $data['amount'] = 0;
        }

        $reseller = ! empty($data['reseller_id']) ? User::where('role', 'reseller')->find($data['reseller_id']) : null;
        $batch = $vouchers->issueBatch($data, $request->user(), $reseller, $reseller && $request->boolean('charge_reseller'));

        return redirect()->route('admin.vouchers.show', $batch)->with('success', 'تم إصدار '.$batch->quantity.' قسيمة.');
    }

    public function show(Request $request, VoucherBatch $batch)
    {
        $batch->load(['reseller', 'course', 'creator']);
        $vouchers = $batch->vouchers()->with('redeemer:id,name,phone')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('id')->paginate(50)->withQueryString();

        return view('admin.vouchers.show', compact('batch', 'vouchers'));
    }

    public function search(Request $request)
    {
        $code = VoucherService::normalize((string) $request->query('code'));
        $voucher = $code ? Voucher::with(['batch', 'redeemer', 'reseller', 'course'])->where('code', $code)->first() : null;

        return view('admin.vouchers.search', compact('voucher', 'code'));
    }

    public function print(VoucherBatch $batch)
    {
        $vouchers = $batch->vouchers()->where('status', 'active')->orderBy('id')->get();

        return view('partials.voucher-print', compact('batch', 'vouchers'));
    }

    public function csv(VoucherBatch $batch)
    {
        return ResellerController::exportCsv($batch);
    }

    public function disableBatch(VoucherBatch $batch)
    {
        $n = $batch->vouchers()->where('status', 'active')->update(['status' => 'disabled']);

        return back()->with('success', "تم تعطيل $n قسيمة غير مستخدمة.");
    }

    public function toggle(Voucher $voucher)
    {
        if ($voucher->status === 'redeemed') {
            return back()->with('error', 'القسيمة مستخدمة ولا يمكن تغييرها.');
        }
        $voucher->update(['status' => $voucher->status === 'active' ? 'disabled' : 'active']);

        return back()->with('success', 'تم تحديث حالة القسيمة.');
    }
}
