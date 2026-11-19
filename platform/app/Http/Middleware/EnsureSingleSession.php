<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** جلسة ويب واحدة لكل حساب: الدخول من متصفح ثاني يطلع الأول */
class EnsureSingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $kicked = null;
            if (! $user->is_active) {
                $kicked = 'تم إيقاف حسابك. تواصل مع الدعم.';
            } elseif (setting('single_session') && ! $user->isAdmin()
                && $user->web_session_id && $user->web_session_id !== $request->session()->getId()) {
                $kicked = 'تم تسجيل الدخول لحسابك من جهاز آخر، فخرجنا من هذا الجهاز.';
            }

            if ($kicked) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', $kicked);
            }
        }

        return $next($request);
    }
}
