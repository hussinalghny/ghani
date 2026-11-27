<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\AcademyException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeviceGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private DeviceGuard $devices) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], ['phone' => 'رقم الهاتف', 'password' => 'كلمة المرور']);

        $user = User::query()->where('phone', $data['phone'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withInput($request->only('phone'))->with('error', 'رقم الهاتف أو كلمة المرور غير صحيحة.');
        }
        if (! $user->is_active) {
            return back()->withInput($request->only('phone'))->with('error', 'تم إيقاف حسابك. تواصل مع الدعم.');
        }

        return $this->startSession($request, $user);
    }

    public function register(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/', 'unique:users,phone'],
            'city' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'اكتب رقم هاتف عراقي صحيح بصيغة 07XXXXXXXXX.',
            'phone.unique' => 'هذا الرقم مسجل سابقا. سجل دخولك.',
        ], ['name' => 'الاسم', 'phone' => 'رقم الهاتف', 'password' => 'كلمة المرور', 'city' => 'المحافظة']);

        $user = User::create($data);

        return $this->startSession($request, $user, 'أهلا '.$user->name.'. حسابك جاهز، اختر كورسك الأول.');
    }

    private function startSession(Request $request, User $user, ?string $welcome = null)
    {
        $deviceId = $this->webDeviceId() ?: (string) Str::uuid();

        try {
            $this->devices->register($user, $deviceId, 'web', $this->deviceName($request->userAgent()), $request->ip());
        } catch (AcademyException $e) {
            return back()->withInput($request->only('phone'))->with('error', $e->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill([
            'web_session_id' => $request->session()->getId(),
            'last_login_at' => now(),
        ])->save();

        Cookie::queue(Cookie::make('ga_did', $deviceId, 60 * 24 * 365 * 5, null, null, null, true, false, 'Lax'));

        $home = match (true) {
            $user->isAdmin() => route('admin.dashboard'),
            $user->isInstructor() => route('teach'),
            $user->isSupport() => route('inbox'),
            $user->isReseller() => route('reseller.index'),
            default => route('account'),
        };

        return redirect()->intended($home)->with('success', $welcome ?? 'أهلا بعودتك '.$user->name.'.');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        Auth::guard('web')->logout();
        $user?->forceFill(['web_session_id' => null])->save();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
