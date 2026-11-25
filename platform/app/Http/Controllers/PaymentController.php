<?php

namespace App\Http\Controllers;

use App\Exceptions\AcademyException;
use App\Models\Course;
use App\Models\PaymentOrder;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** الدفع المباشر: الطالب يحول مبلغ مميز لمحفظة الأكاديمية والطلب يتفعل تلقائيا */
class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(PaymentService::methods()))],
            'course' => ['nullable', 'integer', 'exists:courses,id'],
            'registration' => ['nullable', 'string', 'exists:cohort_registrations,code'],
            'amount' => ['nullable', 'integer', 'min:1000', 'max:5000000'],
        ], [], ['method' => 'طريقة الدفع', 'amount' => 'المبلغ']);

        $user = $request->user();
        $course = isset($data['course']) ? Course::published()->findOrFail($data['course']) : null;
        if ($course && $user->hasAccessTo($course)) {
            return redirect()->route('learn.show', $course)->with('success', 'أنت مشترك بهذا الكورس.');
        }
        $reg = isset($data['registration']) ? \App\Models\CohortRegistration::where('code', $data['registration'])->where('user_id', $user->id)->firstOrFail() : null;
        if ($reg && $reg->status !== 'pending') {
            return redirect()->route('cohort.ticket', $reg);
        }
        // للكورس أو المقعد: نطلب الفرق بس إذا عنده رصيد
        $price = $reg ? $reg->cohort->price : ($course ? $course->finalPrice() : null);
        $base = $price !== null ? max(1000, $price - (int) $user->balance) : (int) ($data['amount'] ?? 0);

        try {
            $order = $this->payments->create($user, $data['method'], $base, $reg ? null : $course, $reg);
        } catch (AcademyException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pay.show', $order);
    }

    public function show(Request $request, PaymentOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $this->payments->expireOld();
        $order->refresh();

        return view('pay.show', ['order' => $order, 'method' => PaymentService::methods()[$order->method] ?? null]);
    }

    public function status(Request $request, PaymentOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return response()->json([
            'status' => $order->status,
            'redirect' => $order->status === 'paid' ? ($order->registration ? route('cohort.ticket', $order->registration) : ($order->course ? route('learn.show', $order->course) : route('wallet'))) : null,
        ])->header('Cache-Control', 'no-store');
    }

    public function cancel(Request $request, PaymentOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id && $order->status === 'pending', 404);
        $order->update(['status' => 'cancelled']);

        if ($order->registration) {
            return redirect()->route('cohort.show', $order->registration->cohort)->with('success', 'تم إلغاء طلب الدفع. مقعدك محجوز 48 ساعة.');
        }

        return redirect()->route($order->course ? 'courses.show' : 'wallet', $order->course ?? [])->with('success', 'تم إلغاء الطلب.');
    }
}
