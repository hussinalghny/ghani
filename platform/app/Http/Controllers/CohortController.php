<?php

namespace App\Http\Controllers;

use App\Exceptions\AcademyException;
use App\Models\Cohort;
use App\Models\CohortRegistration;
use App\Models\DiscountCode;
use App\Models\User;
use App\Services\CohortService;
use App\Services\DeviceGuard;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** صفحة الكورس الحضوري والتسجيل والتذكرة */
class CohortController extends Controller
{
    public function __construct(private CohortService $cohorts) {}

    public function show(Cohort $cohort)
    {
        $user = auth()->user();
        $mine = $user ? $cohort->registrations()->where('user_id', $user->id)->whereIn('status', ['pending', 'paid', 'waitlist'])->first() : null;

        return view('site.cohort', [
            'cohort' => $cohort->load('course.instructor'),
            'left' => $cohort->left(),
            'mine' => $mine,
            'methods' => PaymentService::enabled() ? PaymentService::methods() : [],
        ]);
    }

    public function register(Request $request, Cohort $cohort, DeviceGuard $devices)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $guest = ! auth()->check();
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => array_merge(['required', 'regex:/^07[3-9]\d{8}$/'], $guest ? ['unique:users,phone'] : []),
            'email' => ['nullable', 'email', 'max:120'],
            'job' => ['nullable', 'string', 'max:80'],
            'level' => ['required', Rule::in(array_keys(CohortRegistration::LEVELS))],
            'notes' => ['nullable', 'string', 'max:500'],
            'discount' => ['nullable', 'string', 'max:40'],
            'password' => $guest ? ['required', 'string', 'min:8'] : ['nullable'],
            'agree' => ['accepted'],
        ], [
            'phone.regex' => 'اكتب رقم هاتف عراقي صحيح بصيغة 07XXXXXXXXX.',
            'phone.unique' => 'هذا الرقم عنده حساب. سجل دخولك أولا وكمل التسجيل.',
            'agree.accepted' => 'لازم توافق على شروط التسجيل.',
        ], ['name' => 'الاسم', 'phone' => 'رقم الهاتف', 'password' => 'كلمة المرور', 'level' => 'مستواك', 'email' => 'الإيميل']);

        $discount = DiscountCode::check($data['discount'] ?? null, $cohort);
        if (is_string($discount)) {
            return back()->withInput($request->except('password'))->withErrors(['discount' => $discount]);
        }

        if ($guest) {
            $user = User::create(['name' => $data['name'], 'phone' => $data['phone'], 'password' => $data['password']]);
            $deviceId = (string) \Illuminate\Support\Str::uuid();
            try {
                $devices->register($user, $deviceId, 'web', 'Web', $request->ip());
            } catch (AcademyException) {
            }
            \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::make('ga_did', $deviceId, 60 * 24 * 365 * 5, null, null, null, true, false, 'Lax'));
            Auth::login($user);
            $request->session()->regenerate();
            $user->forceFill(['web_session_id' => $request->session()->getId(), 'last_login_at' => now()])->save();
        }
        $user = $request->user();

        $reg = $this->cohorts->register($cohort, $user, collect($data)->only(['name', 'phone', 'email', 'job', 'level', 'notes'])->all());
        if ($discount && $reg->status === 'pending') {
            $this->cohorts->applyDiscount($reg, $discount->code);
        }

        if ($reg->status === 'waitlist') {
            return redirect()->route('cohort.ticket', $reg)->with('success', 'المقاعد اكتملت، وسجلناك بقائمة الانتظار. نتواصل وياك أول ما يتوفر مقعد.');
        }
        if ($reg->status === 'paid') {
            return redirect()->route('cohort.ticket', $reg);
        }

        return redirect(route('cohort.show', $cohort).'#pay')->with('success', 'انحجز مقعدك 48 ساعة. كمل الدفع حتى يتأكد.');
    }

    public function discount(Request $request, CohortRegistration $registration)
    {
        abort_unless($registration->user_id === $request->user()->id && $registration->status === 'pending', 404);
        $err = $this->cohorts->applyDiscount($registration, $request->input('discount'));
        if ($err) {
            return redirect(route('cohort.show', $registration->cohort).'#pay')->withErrors(['discount' => $err]);
        }

        return redirect(route('cohort.show', $registration->cohort).'#pay')->with('success', $request->filled('discount') ? 'انطبق كود الخصم.' : 'انشال كود الخصم.');
    }

    public function wallet(Request $request, CohortRegistration $registration)
    {
        abort_unless($registration->user_id === $request->user()->id, 404);
        try {
            $this->cohorts->payFromWallet($registration);
        } catch (AcademyException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('cohort.ticket', $registration)->with('success', 'تم تأكيد مقعدك.');
    }

    public function ticket(Request $request, CohortRegistration $registration)
    {
        $user = $request->user();
        abort_unless($registration->user_id === $user->id || $user->isAdmin() || $user->isSupport(), 404);

        return view('site.cohort-ticket', ['r' => $registration->load('cohort')]);
    }
}
