<?php

if (! function_exists('iqd')) {
    /** تنسيق المبالغ: 25,000 د.ع */
    function iqd(int|float|null $amount, bool $withCurrency = true): string
    {
        $v = number_format((int) $amount);

        return $withCurrency ? $v.' د.ع' : $v;
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return \App\Models\Setting::get($key, $default);
    }
}

if (! function_exists('normalize_phone')) {
    /** يحول الرقم إلى صيغة 07XXXXXXXXX ويقبل الأرقام العربية و+964 */
    function normalize_phone(?string $phone): string
    {
        $phone = strtr((string) $phone, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '00964')) {
            $phone = '0'.substr($phone, 5);
        } elseif (str_starts_with($phone, '964')) {
            $phone = '0'.substr($phone, 3);
        } elseif (strlen($phone) === 10 && str_starts_with($phone, '7')) {
            $phone = '0'.$phone;
        }

        return $phone;
    }
}

if (! function_exists('duration_human')) {
    function duration_human(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($h && $m) {
            return "$h ساعة و $m دقيقة";
        }

        return $h ? "$h ساعة" : max($m, 1).' دقيقة';
    }
}
