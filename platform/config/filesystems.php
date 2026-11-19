<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        // ملفات الدروس المحمية: لا تنفتح إلا برابط موقع مؤقت
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
        ],

        // صور الكورسات والمدربين وملفات التطبيق
        // مباشرة داخل public/storage (بدون symlink لأن Hostinger يمنعه)
        'public' => [
            'driver' => 'local',
            'root' => public_path('storage'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [],
];
