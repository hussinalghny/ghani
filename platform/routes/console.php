<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=24')->daily();
// غني باي: تنبيه إذا الموبايل انقطع (يشتغل أيضا عند فتح صفحات الدفع)
Schedule::call(fn () => app(App\Services\PaymentService::class)->watchdog())->everyMinute()->name('pay-watchdog')->withoutOverlapping();
