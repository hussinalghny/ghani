<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Setting;
use App\Services\R2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** تخزين الفيديوهات: إعداد Cloudflare R2 ونقل الفيديوهات الحالية */
class StorageController extends Controller
{
    public function index()
    {
        $lessons = Lesson::with('course:id,title')->whereNotNull('video_path')->orderBy('course_id')->orderBy('sort')->get();
        $local = $lessons->reject(fn ($l) => R2::isR2($l->video_path))->map(function ($l) {
            $f = storage_path('app/private/'.$l->video_path);
            $l->size_mb = is_file($f) ? round(filesize($f) / 1048576, 1) : null;

            return $l;
        })->values();
        $onR2 = $lessons->filter(fn ($l) => R2::isR2($l->video_path))->count();

        return view('admin.storage', [
            'local' => $local,
            'onR2' => $onR2,
            'conf' => [
                'account' => Setting::get('r2_account_id'),
                'key' => Setting::get('r2_access_key'),
                'bucket' => Setting::get('r2_bucket'),
                'has_secret' => (bool) Setting::get('r2_secret'),
                'enabled' => Setting::get('r2_enabled') === '1',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'r2_account_id' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/'],
            'r2_access_key' => ['required', 'string', 'max:128'],
            'r2_secret' => ['nullable', 'string', 'max:200'],
            'r2_bucket' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/'],
        ], [
            'r2_account_id.regex' => 'Account ID يتكون من 32 حرف ورقم (تلگاه بصفحة R2 بكلاودفلير).',
            'r2_bucket.regex' => 'اسم الـ bucket حروف صغيرة وأرقام وشرطة فقط.',
        ]);
        $values = [
            'r2_account_id' => trim($data['r2_account_id']),
            'r2_access_key' => trim($data['r2_access_key']),
            'r2_bucket' => trim($data['r2_bucket']),
            'r2_enabled' => $request->boolean('r2_enabled') ? '1' : '0',
        ];
        if (! empty($data['r2_secret'])) {
            $values['r2_secret'] = Crypt::encryptString(trim($data['r2_secret']));
        }
        Setting::put($values);

        if ($request->boolean('test')) {
            $r = R2::test();

            return back()->with($r === 'ok' ? 'success' : 'error', $r === 'ok' ? 'الاتصال بـ R2 يشتغل: رفع وقراءة وحذف ملف تجربة نجحت.' : 'فشل الاتصال: '.$r);
        }

        return back()->with('success', 'تم الحفظ.');
    }

    /** ينقل فيديو درس واحد (يستدعى من الصفحة واحد ورا الثاني) */
    public function move(Lesson $lesson)
    {
        abort_unless(R2::conf(), 422, 'اضبط إعدادات R2 أولا.');
        try {
            R2::moveLesson($lesson);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true]);
    }
}
