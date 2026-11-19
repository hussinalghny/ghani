<?php

return [
    'whatsapp' => '07766637444',
    'currency' => 'د.ع',

    'bunny' => [
        'library_id' => env('BUNNY_LIBRARY_ID'),
        'api_key' => env('BUNNY_API_KEY'),
        'embed_token_key' => env('BUNNY_EMBED_TOKEN_KEY'),
        'cdn_host' => env('BUNNY_CDN_HOST'),
        'cdn_token_key' => env('BUNNY_CDN_TOKEN_KEY'),
        'token_ttl' => (int) env('BUNNY_TOKEN_TTL', 120),
    ],

    // القيم الافتراضية للإعدادات (تتغير من لوحة التحكم)
    'defaults' => [
        'max_devices' => 2,
        'single_session' => 1,
        'watermark' => 1,
        'voucher_denominations' => '5000,10000,25000,50000,100000,200000',
        'reseller_discount' => 10,
        'lesson_opens_per_hour' => 40,
        'announcement' => '',
    ],
];
