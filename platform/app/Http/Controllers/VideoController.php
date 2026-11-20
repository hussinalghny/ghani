<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;
use App\Services\LessonAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * مشغل فيديو مجاني على سيرفرنا:
 * - رابط موقع ينتهي بعد مدة ومربوط برقم الطالب (ما ينفع ينشارك)
 * - يدعم التقديم والتأخير (Range) حتى يشتغل بالموقع والتطبيق
 * - رفع الفيديو على أجزاء حتى ما نصطدم بحد رفع PHP بـ Hostinger
 */
class VideoController extends Controller
{
    public const EXT = ['mp4', 'm4v', 'webm', 'mov'];

    /** رابط الموقع: موقّع، وينتهي، ومربوط بجلسة المتصفح نفسها (نسخه لمتصفح ثاني ما يشتغل) */
    public static function webUrl(Lesson $lesson): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute('video.web', now()->addHours(4), [
            'lesson' => $lesson->id,
            's' => substr(hash_hmac('sha256', session()->getId(), config('app.key')), 0, 24),
        ]);
    }

    public function web(Request $request, Lesson $lesson, LessonAccess $access)
    {
        $expected = substr(hash_hmac('sha256', $request->session()->getId(), config('app.key')), 0, 24);
        abort_unless(hash_equals($expected, (string) $request->query('s')), 403);
        // المتصفح يطلب الفيديو كعنصر video فقط، مو كصفحة أو تنزيل
        abort_if($request->header('Sec-Fetch-Dest') === 'document', 403);

        return $this->stream($request, $lesson, $access);
    }

    /** للتطبيق: Authorization: Bearer (الرابط وحده ما يشتغل) */
    public function stream(Request $request, Lesson $lesson, LessonAccess $access)
    {
        $user = $request->user();
        abort_if(! $user || ! $user->is_active, 403);
        $access->authorize($user, $lesson);

        $path = storage_path('app/private/'.$lesson->video_path);
        abort_unless($lesson->video_path && is_file($path), 404);

        $type = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            default => 'video/mp4',
        };

        return response()->file($path, [
            'Content-Type' => $type,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /** رفع جزء: upload_id, index, total, name, chunk */
    public function chunk(Request $request)
    {
        $data = $request->validate([
            'upload_id' => ['required', 'alpha_num', 'min:10', 'max:40'],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1', 'max:2000'],
            'name' => ['required', 'string', 'max:200'],
            'chunk' => ['required', 'file', 'max:20480'],
        ]);
        $ext = strtolower(pathinfo($data['name'], PATHINFO_EXTENSION));
        abort_unless(in_array($ext, self::EXT, true), 422, 'صيغة الفيديو غير مدعومة. استخدم MP4.');

        $dir = storage_path('app/private/videos/tmp');
        @mkdir($dir, 0755, true);
        $part = $dir.'/'.$data['upload_id'].'.part';
        if ((int) $data['index'] === 0 && is_file($part)) {
            unlink($part);
        }
        file_put_contents($part, file_get_contents($request->file('chunk')->getRealPath()), FILE_APPEND | LOCK_EX);

        if ((int) $data['index'] + 1 < (int) $data['total']) {
            return response()->json(['ok' => true]);
        }

        $rel = 'videos/'.now()->format('Y-m').'/'.Str::lower(Str::random(24)).'.'.$ext;
        @mkdir(dirname(storage_path('app/private/'.$rel)), 0755, true);
        rename($part, storage_path('app/private/'.$rel));

        return response()->json(['ok' => true, 'path' => $rel, 'size' => round(filesize(storage_path('app/private/'.$rel)) / 1048576, 1)]);
    }
}
