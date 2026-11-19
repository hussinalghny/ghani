<?php

use App\Http\Middleware\EnsureActiveDevice;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureSingleSession;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureRole::class,
            'device' => EnsureActiveDevice::class,
        ]);
        $middleware->web(append: [
            EnsureSingleSession::class,
            SecurityHeaders::class,
        ]);
        $middleware->encryptCookies(except: []);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('account'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport(\App\Exceptions\AcademyException::class);
        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*') || $r->expectsJson());
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'يجب تسجيل الدخول.'], 401);
            }
        });
        $exceptions->render(function (\App\Exceptions\AcademyException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'code' => $e->errorCode], $e->status);
            }
            return back()->withInput()->with('error', $e->getMessage());
        });
    })->create();
