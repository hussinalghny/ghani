<?php

namespace App\Http\Controllers;

use App\Models\Course;

/** يولد نسخة مصغرة من غلاف الكورس أول مرة، وبعدها تنخدم مباشرة كملف ثابت */
class ThumbController extends Controller
{
    public function __invoke(int $id)
    {
        $course = Course::findOrFail($id);
        abort_unless($course->cover, 404);
        $src = storage_path('app/public/'.$course->cover);
        abort_unless(is_file($src), 404);
        $rel = 'thumbs/c'.$course->id.'-'.substr(md5($course->cover), 0, 8).'.jpg';
        $dst = storage_path('app/public/'.$rel);

        if (! is_file($dst)) {
            try {
                @mkdir(dirname($dst), 0755, true);
                $img = @imagecreatefromstring((string) file_get_contents($src));
                if (! $img) {
                    return redirect($course->coverUrl());
                }
                $w = imagesx($img);
                $h = imagesy($img);
                $nw = min(480, $w);
                $nh = (int) round($h * $nw / max(1, $w));
                $out = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagejpeg($out, $dst, 78);
                imagedestroy($img);
                imagedestroy($out);
            } catch (\Throwable $e) {
                report($e);

                return redirect($course->coverUrl());
            }
        }

        return response()->file($dst, ['Cache-Control' => 'public, max-age=2592000']);
    }
}
