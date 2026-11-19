<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** لطلبات التطبيق: التوكن مربوط بجهاز، وإذا انحظر الجهاز أو الحساب يتوقف فورا */
class EnsureActiveDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $user->is_active) {
            $token?->delete();

            return response()->json(['message' => 'تم إيقاف الحساب.', 'code' => 'account_disabled'], 403);
        }

        if ($token && method_exists($token, 'getKey')) {
            $device = Device::query()->where('user_id', $user->id)->where('device_id', $token->name)->first();
            if (! $device || $device->is_blocked) {
                $token->delete();

                return response()->json(['message' => 'هذا الجهاز لم يعد مسموحا. سجل الدخول من جديد.', 'code' => 'device_revoked'], 401);
            }
            if (! $device->last_seen_at || $device->last_seen_at->lt(now()->subMinutes(10))) {
                $device->forceFill(['last_seen_at' => now(), 'last_ip' => $request->ip()])->save();
            }
            $request->attributes->set('device', $device);
        }

        return $next($request);
    }
}
