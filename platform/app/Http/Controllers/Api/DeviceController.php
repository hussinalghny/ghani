<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeviceController extends Controller
{
    /** تسجيل توكن الإشعارات للجهاز */
    public function storeToken(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:255'], 'platform' => ['nullable', 'in:android,ios']]);
        PushToken::updateOrCreate(['token' => $data['token']], ['user_id' => $request->user()->id, 'platform' => $data['platform'] ?? 'android']);

        return response()->json(['ok' => true]);
    }

    public function deleteToken(Request $request)
    {
        PushToken::where('user_id', $request->user()->id)->where('token', (string) $request->input('token'))->delete();

        return response()->json(['ok' => true]);
    }

    /** حذف الحساب نهائيا (مطلب Apple و Google) */
    public function destroyAccount(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();
        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'كلمة المرور غير صحيحة.', 'code' => 'invalid_password'], 422);
        }
        abort_if($user->isAdmin(), 403, 'لا يمكن حذف حساب المدير من التطبيق.');
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'تم حذف حسابك وكل بياناتك.']);
    }
}
