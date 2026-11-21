<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentNotification;
use App\Models\PaymentOrder;
use App\Services\PaymentService;
use Illuminate\Http\Request;

/** لوحة تطبيق غني باي: الطلبات والمراجعة والتأكيد، بنفس توقيع الإشعارات */
class PayConsoleController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public static function verify(Request $request): void
    {
        $secret = (string) setting('pay_hook_secret');
        $time = (int) $request->header('X-Ghani-Time');
        $sign = (string) $request->header('X-Ghani-Sign');
        abort_if($secret === '' || abs(time() - $time) > 600, 401, 'expired');
        abort_unless(hash_equals(hash_hmac('sha256', $time.'.'.$request->getContent(), $secret), $sign), 401, 'bad signature');
    }

    /** نبض الموبايل كل دقيقة: {battery, charging, queue, version} */
    public function heartbeat(Request $request)
    {
        self::verify($request);
        $d = $request->validate([
            'battery' => ['nullable', 'integer', 'between:0,100'], 'charging' => ['nullable', 'boolean'],
            'queue' => ['nullable', 'integer', 'min:0'], 'version' => ['nullable', 'string', 'max:20'],
        ]);
        $this->payments->heartbeat($d);

        return response()->json(['ok' => true]);
    }

    public function feed(Request $request)
    {
        self::verify($request);
        $this->payments->expireOld();
        $this->payments->watchdogLazy();
        $tz = 'Asia/Baghdad';
        $order = fn (PaymentOrder $o) => [
            'id' => $o->id, 'amount' => $o->amount, 'base' => $o->base_amount, 'method' => $o->method,
            'user' => $o->user?->name, 'phone' => $o->user?->phone, 'course' => $o->course?->title ?? 'شحن محفظة',
            'created' => $o->created_at->timezone($tz)->format('m/d H:i'), 'paid' => $o->paid_at?->timezone($tz)->format('m/d H:i'), 'by' => $o->paid_by,
        ];

        return response()->json([
            'stats' => [
                'today' => (int) PaymentOrder::where('status', 'paid')->where('paid_at', '>=', today())->sum('amount'),
                'month' => (int) PaymentOrder::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'pending' => PaymentOrder::where('status', 'pending')->count(),
                'review' => PaymentNotification::where('status', 'review')->count(),
            ],
            'wallets' => collect(PaymentService::methods())->map(fn ($m, $k) => ['key' => $k, 'label' => $m['label'], 'number' => $m['number']])->values(),
            'pending' => PaymentOrder::with(['user:id,name,phone', 'course:id,title'])->where('status', 'pending')->latest()->limit(40)->get()->map($order),
            'review' => PaymentNotification::where('status', 'review')->latest()->limit(30)->get()->map(function ($n) use ($tz) {
                $s = $n->amount ? PaymentOrder::with('user:id,name')->whereIn('status', ['pending', 'expired'])
                    ->whereBetween('amount', [$n->amount - 1000, $n->amount + 1000])->orderByRaw('ABS(amount - ?)', [$n->amount])->first() : null;

                return ['id' => $n->id, 'amount' => $n->amount, 'text' => $n->text, 'at' => $n->received_at?->timezone($tz)->format('m/d H:i'),
                    'suggest' => $s ? ['id' => $s->id, 'user' => $s->user?->name, 'amount' => $s->amount] : null];
            }),
            'recent' => PaymentOrder::with(['user:id,name,phone', 'course:id,title'])->where('status', 'paid')->latest('paid_at')->limit(20)->get()->map($order),
        ])->header('Cache-Control', 'no-store');
    }

    public function approve(Request $request, PaymentOrder $order)
    {
        self::verify($request);
        abort_unless(in_array($order->status, ['pending', 'expired'], true), 422, 'الطلب مدفوع أو ملغي.');
        $order->update(['status' => 'pending']);
        $this->payments->fulfill($order, null, 'admin');

        return response()->json(['ok' => true]);
    }

    public function attach(Request $request, PaymentNotification $notification)
    {
        self::verify($request);
        $order = PaymentOrder::findOrFail((int) $request->input('order'));
        abort_unless(in_array($order->status, ['pending', 'expired'], true), 422, 'الطلب مدفوع أو ملغي.');
        $order->update(['status' => 'pending']);
        $this->payments->fulfill($order, $notification, 'admin');

        return response()->json(['ok' => true]);
    }

    public function ignore(Request $request, PaymentNotification $notification)
    {
        self::verify($request);
        $notification->update(['status' => 'ignored']);

        return response()->json(['ok' => true]);
    }
}
