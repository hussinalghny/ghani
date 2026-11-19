<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\DeviceGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private DeviceGuard $devices) {}

    private function deviceRules(): array
    {
        return [
            'device_id' => ['required', 'string', 'min:6', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['required', 'in:android,ios'],
        ];
    }

    public function register(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/', 'unique:users,phone'],
            'city' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8'],
        ] + $this->deviceRules(), [
            'phone.regex' => 'اكتب رقم هاتف عراقي صحيح بصيغة 07XXXXXXXXX.',
            'phone.unique' => 'هذا الرقم مسجل سابقا.',
        ]);

        $user = User::create($request->only('name', 'phone', 'city', 'password'));

        return $this->issueToken($request, $user, 201);
    }

    public function login(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $request->validate(['phone' => ['required'], 'password' => ['required']] + $this->deviceRules());

        $user = User::where('phone', $request->input('phone'))->first();
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'رقم الهاتف أو كلمة المرور غير صحيحة.', 'code' => 'invalid_credentials'], 422);
        }
        if (! $user->is_active) {
            return response()->json(['message' => 'تم إيقاف حسابك.', 'code' => 'account_disabled'], 403);
        }

        return $this->issueToken($request, $user);
    }

    private function issueToken(Request $request, User $user, int $status = 200)
    {
        $device = $this->devices->register($user, $request->input('device_id'), $request->input('platform'), $request->input('device_name'), $request->ip());

        // جلسة تطبيق واحدة: أي توكن قديم ينحذف
        if (setting('single_session') && ! $user->isAdmin()) {
            $user->tokens()->delete();
        } else {
            $user->tokens()->where('name', $device->device_id)->delete();
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($device->device_id, ['student'], now()->addDays(60));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => new UserResource($user),
        ], $status);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }
}
