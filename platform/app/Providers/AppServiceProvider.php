<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\BunnyStream::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        \Carbon\Carbon::setLocale(config('app.locale', 'ar'));
        Paginator::defaultView('vendor.pagination.apple');
        Paginator::defaultSimpleView('vendor.pagination.apple');

        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(6)->by('login|'.$r->ip()),
            Limit::perMinute(5)->by('login|'.normalize_phone($r->input('phone'))),
        ]);

        // منع تخمين أكواد القسائم
        RateLimiter::for('redeem', fn (Request $r) => [
            Limit::perMinute(5)->by('redeem|'.($r->user()?->id ?: $r->ip())),
            Limit::perHour(30)->by('redeem-h|'.($r->user()?->id ?: $r->ip())),
        ]);

        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
    }
}
