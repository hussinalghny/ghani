<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** معرف الجهاز للمتصفح: كوكي مشفرة طويلة الأمد */
    protected function webDeviceId(): ?string
    {
        return request()->cookie('ga_did');
    }

    protected function deviceName(?string $ua): string
    {
        $ua = (string) $ua;
        $os = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'Mac',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'جهاز',
        };
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'متصفح',
        };

        return "$browser · $os";
    }
}
