<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * خط ثمانية: ينزل مرة وحدة من الرابط الرسمي لمجلد public/fonts/thmanyah
 * (ثمانية تمنع إعادة توزيع ملفات الخط، فما نرفعها للمستودع)
 */
class ThmanyahFont
{
    public const URL = 'https://public-assets.thmanyah.com/font/Thmanyah-Font-Family.zip';
    public const FILES = [
        'thmanyahsans-Regular', 'thmanyahsans-Medium', 'thmanyahsans-Bold', 'thmanyahsans-Black',
        'thmanyahserifdisplay-Medium', 'thmanyahserifdisplay-Bold', 'thmanyahserifdisplay-Black',
    ];

    public static function dir(): string
    {
        return public_path('fonts/thmanyah');
    }

    public static function ready(): bool
    {
        return is_file(self::dir().'/thmanyahsans-Regular.woff2');
    }

    /** يشتغل بالخلفية أول طلب، ومحاولة وحدة كل ساعة إذا فشل */
    public static function ensure(): void
    {
        if (self::ready() || ! class_exists(\ZipArchive::class)) {
            return;
        }
        $flag = storage_path('app/thmanyah.try');
        if (is_file($flag) && filemtime($flag) > time() - 3600) {
            return;
        }
        @touch($flag);
        $tmp = storage_path('app/thmanyah.zip');
        try {
            Http::timeout(90)->withOptions(['sink' => $tmp])->get(self::URL)->throw();
            $zip = new \ZipArchive;
            if ($zip->open($tmp) !== true) {
                return;
            }
            @mkdir(self::dir(), 0755, true);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_contains($name, '__MACOSX') || ! str_ends_with($name, '.woff2')) {
                    continue;
                }
                $base = basename($name, '.woff2');
                if (in_array($base, self::FILES, true)) {
                    file_put_contents(self::dir().'/'.$base.'.woff2', $zip->getFromIndex($i));
                }
            }
            $zip->close();
        } catch (\Throwable) {
            // نحاول بعد ساعة، والموقع يبقى بالخط الاحتياطي
        } finally {
            @unlink($tmp);
        }
    }
}
