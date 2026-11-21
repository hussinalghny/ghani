<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentNotification;
use App\Models\PaymentOrder;
use App\Models\Setting;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request)
    {
        $this->payments->expireOld();
        $this->payments->watchdogLazy();
        $status = $request->query('status', 'pending');
        $orders = PaymentOrder::with(['user:id,name,phone', 'course:id,title'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()->paginate(30)->withQueryString();
        $review = PaymentNotification::where('status', 'review')->latest()->limit(30)->get();
        $recent = PaymentNotification::whereIn('status', ['matched', 'ignored'])->latest()->limit(15)->get();
        $stats = [
            'today' => (int) PaymentOrder::where('status', 'paid')->where('paid_at', '>=', today())->sum('amount'),
            'month' => (int) PaymentOrder::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'pending' => PaymentOrder::where('status', 'pending')->count(),
            'auto' => PaymentOrder::where('status', 'paid')->where('paid_by', 'auto')->where('paid_at', '>=', now()->startOfMonth())->count(),
        ];
        if (! setting('pay_hook_secret')) {
            Setting::put(['pay_hook_secret' => Str::random(40)]);
        }

        return view('admin.payments', compact('orders', 'review', 'recent', 'stats', 'status'));
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'pay_qi_number' => ['nullable', 'string', 'max:40'], 'pay_qi_name' => ['nullable', 'string', 'max:80'],
            'pay_zaincash_number' => ['nullable', 'string', 'max:40'], 'pay_zaincash_name' => ['nullable', 'string', 'max:80'],
            'pay_fastpay_number' => ['nullable', 'string', 'max:40'], 'pay_fastpay_name' => ['nullable', 'string', 'max:80'],
            'pay_tg_token' => ['nullable', 'string', 'max:120'], 'pay_tg_chat' => ['nullable', 'string', 'max:40'],
        ]);
        $data = array_map(fn ($v) => (string) $v, $data);
        $data['pay_enabled'] = $request->boolean('pay_enabled') ? 1 : 0;
        if ($request->boolean('new_secret')) {
            $data['pay_hook_secret'] = Str::random(40);
        }
        Setting::put($data);
        if ($request->boolean('tg_test')) {
            return $this->payments->alert('✅ تجربة تنبيهات غني باي من '.url('/'))
                ? back()->with('success', 'وصلت رسالة التجربة للتيليگرام.')
                : back()->with('error', 'ما وصلت الرسالة. شيك توكن البوت ورقم المحادثة، وتأكد دزيت /start للبوت.');
        }

        return back()->with('success', $request->boolean('new_secret') ? 'تم تغيير المفتاح. حدثه بتطبيق غني باي.' : 'تم الحفظ.');
    }

    public function approve(PaymentOrder $order)
    {
        abort_unless(in_array($order->status, ['pending', 'expired'], true), 422);
        $order->update(['status' => 'pending']);
        $this->payments->fulfill($order, null, 'admin');

        return back()->with('success', 'تم تأكيد الدفع وتفعيل الطلب.');
    }

    public function attach(Request $request, PaymentNotification $notification)
    {
        $data = $request->validate(['order' => ['required', 'integer', 'exists:payment_orders,id']]);
        $order = PaymentOrder::findOrFail($data['order']);
        abort_unless(in_array($order->status, ['pending', 'expired'], true), 422, 'الطلب مدفوع أو ملغي.');
        $order->update(['status' => 'pending']);
        $this->payments->fulfill($order, $notification, 'admin');

        return back()->with('success', 'تم ربط الدفعة بالطلب وتفعيله.');
    }

    public function ignore(PaymentNotification $notification)
    {
        $notification->update(['status' => 'ignored']);

        return back()->with('success', 'تم تجاهل الإشعار.');
    }
}
