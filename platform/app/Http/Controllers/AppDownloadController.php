<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/** صفحة تنزيل التطبيق: الملفات ترتفع من لوحة التحكم بدون SSH */
class AppDownloadController extends Controller
{
    public static function release(): array
    {
        $r = [];
        foreach (['android', 'ios'] as $p) {
            $file = setting("app_{$p}_file");
            $url = setting("app_{$p}_url");
            $r[$p] = [
                'available' => (bool) ($file || $url),
                'size' => $file && Storage::disk('public')->exists($file) ? round(Storage::disk('public')->size($file) / 1048576, 1) : null,
                'downloads' => (int) setting("app_{$p}_downloads", 0),
            ];
        }
        $r['version'] = setting('app_version') ?: '1.0.0';
        $r['notes'] = setting('app_notes');
        $r['updated'] = setting('app_updated_at');

        return $r;
    }

    public function show()
    {
        return view('site.app', ['release' => self::release()]);
    }

    public function download(string $platform)
    {
        abort_unless(in_array($platform, ['android', 'ios'], true), 404);
        $file = setting("app_{$platform}_file");
        $url = setting("app_{$platform}_url");

        if (! $file && ! $url) {
            return redirect()->route('app')->with('error', 'نسخة '.($platform === 'ios' ? 'iPhone' : 'Android').' غير متوفرة حاليا.');
        }

        Setting::put(["app_{$platform}_downloads" => (int) setting("app_{$platform}_downloads", 0) + 1]);

        if ($file && Storage::disk('public')->exists($file)) {
            $ext = $platform === 'ios' ? 'ipa' : 'apk';
            $type = $platform === 'ios' ? 'application/octet-stream' : 'application/vnd.android.package-archive';

            return Storage::disk('public')->download($file, 'ghani-academy-'.(setting('app_version') ?: '1.0.0').'.'.$ext, ['Content-Type' => $type]);
        }

        return redirect()->away($url);
    }
}
