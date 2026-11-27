<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=24')->daily();

// نسخة احتياطية لقاعدة البيانات على R2 (تشتغل وحدها يوميا حتى بدون كرون)
Illuminate\Support\Facades\Artisan::command('academy:backup', function () {
    [$key, $size] = App\Services\Backup::run();
    $this->info("OK $key ".round($size / 1024).' KB');
})->purpose('Backup the database to Cloudflare R2');
Schedule::command('academy:backup')->dailyAt('03:30');
