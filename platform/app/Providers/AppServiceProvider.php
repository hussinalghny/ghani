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

    /** آخر migration بالكود. لما يتغير بعد رفع تحديث، الجداول تتحدث تلقائيا (بدون SSH) */
    public const SCHEMA = '2026_11_27_000001';

    private function autoMigrate(): void
    {
        $flag = storage_path('app/schema.version');
        // ما نشغلها قبل التنصيب (بدون مفتاح أو قاعدة بيانات)
        if ($this->app->runningInConsole() || ! config('app.key') || ! is_file(base_path('.env'))) {
            return;
        }
        if (is_file($flag) && trim((string) file_get_contents($flag)) === self::SCHEMA) {
            return;
        }
        $lock = fopen(storage_path('app/schema.lock'), 'c');
        if ($lock && flock($lock, LOCK_EX | LOCK_NB)) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                file_put_contents($flag, self::SCHEMA);
            } catch (\Throwable $e) {
                report($e);
            } finally {
                flock($lock, LOCK_UN);
            }
        }
    }

    public function boot(): void
    {
        $this->autoMigrate();
        if (! $this->app->runningInConsole()) {
            // نسخة احتياطية يومية بالخلفية بعد ما يوصل الرد للزائر
            $this->app->terminating(fn () => \App\Services\Backup::runIfDue());
        }
        if (! $this->app->runningInConsole() && ! \App\Support\ThmanyahFont::ready()) {
            $this->app->terminating(fn () => \App\Support\ThmanyahFont::ensure());
        }
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
