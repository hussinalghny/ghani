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

        $backups = [];
        $backupError = null;
        if (R2::conf()) {
            try {
                $backups = array_slice(\App\Services\Backup::list(), 0, 10);
            } catch (\Throwable $e) {
                $backupError = $e->getMessage();
            }
        }

        return view('admin.storage', [
            'backups' => $backups,
            'backupError' => $backupError,
            'lastBackup' => \App\Services\Backup::last(),
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

    /** استيراد: يعرض مجلدات R2، ومحتوى المجلد المختار مقسم على فصول (المجلدات الفرعية) */
    public function import(Request $request)
    {
        abort_unless(R2::conf(), 422, 'اضبط إعدادات R2 أولا من صفحة تخزين الفيديو.');
        $prefix = (string) $request->query('folder', '');
        $courses = \App\Models\Course::orderBy('title')->get(['id', 'title']);
        try {
            $root = R2::list('');
            $tree = null;
            if ($prefix !== '') {
                $all = collect(R2::list($prefix, true)['files'])
                    ->filter(fn ($f) => in_array(strtolower(pathinfo($f['key'], PATHINFO_EXTENSION)), \App\Http\Controllers\VideoController::EXT, true))
                    ->values();
                $linked = Lesson::whereIn('video_path', $all->map(fn ($f) => R2::PREFIX.$f['key']))->pluck('title', 'video_path');
                $tree = $all->groupBy(function ($f) use ($prefix) {
                    $rest = substr($f['key'], strlen($prefix));

                    return str_contains($rest, '/') ? explode('/', $rest)[0] : '';
                })->sortKeys(SORT_NATURAL)->map(fn ($files, $sec) => [
                    'title' => $sec === '' ? 'الدروس' : R2::titleFromKey($sec.'.x')[1],
                    'files' => $files->sortBy('key', SORT_NATURAL)->values()->map(fn ($f) => $f + [
                        'title' => R2::titleFromKey($f['key'])[1],
                        'linked' => $linked[R2::PREFIX.$f['key']] ?? null,
                    ]),
                ]);
            }
        } catch (\Throwable $e) {
            return redirect()->route('admin.storage')->with('error', 'ما كدرت أقرا R2: '.$e->getMessage());
        }

        return view('admin.storage-import', compact('root', 'prefix', 'tree', 'courses'));
    }

    /** يسوي الفصول والدروس من الملفات المختارة (ويقرا مدة كل فيديو) */
    public function importStore(Request $request)
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.key' => ['required', 'string', 'max:190'],
            'items.*.size' => ['nullable', 'integer'],
            'items.*.title' => ['required', 'string', 'max:200'],
            'items.*.section' => ['required', 'string', 'max:200'],
            'items.*.on' => ['nullable'],
            'items.*.free' => ['nullable'],
        ], [], ['course_id' => 'الكورس']);
        @set_time_limit(0);
        $course = \App\Models\Course::findOrFail($data['course_id']);
        $made = 0;
        $sections = $course->sections()->get()->keyBy('title');
        $nextSection = (int) $course->sections()->max('sort');
        foreach ($data['items'] as $it) {
            if (empty($it['on'])) {
                continue;
            }
            $path = R2::PREFIX.$it['key'];
            if (Lesson::where('video_path', $path)->exists()) {
                continue;
            }
            $sec = $sections[$it['section']] ?? null;
            if (! $sec) {
                $sec = $course->sections()->create(['title' => $it['section'], 'sort' => ++$nextSection]);
                $sections[$it['section']] = $sec;
            }
            $duration = 0;
            try {
                $duration = R2::mp4Duration($it['key'], (int) ($it['size'] ?? 0));
            } catch (\Throwable) {
            }
            Lesson::create([
                'course_id' => $course->id,
                'section_id' => $sec->id,
                'title' => $it['title'],
                'type' => 'video',
                'video_path' => $path,
                'duration' => $duration,
                'is_free_preview' => ! empty($it['free']),
                'sort' => (int) Lesson::where('section_id', $sec->id)->max('sort') + 1,
            ]);
            $made++;
        }
        cache()->forget('api.home');

        return redirect()->route('admin.storage.import', ['folder' => $request->input('folder')])->with('success', 'انضاف '.$made.' درس لكورس '.$course->title.'.');
    }

    public function backup()
    {
        abort_unless(R2::conf(), 422);
        try {
            [$key, $size] = \App\Services\Backup::run();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'فشلت النسخة الاحتياطية: '.$e->getMessage());
        }

        return back()->with('success', 'انحفظت نسخة احتياطية ('.round($size / 1024).' KB).');
    }

    public function backupDownload(Request $request)
    {
        $key = (string) $request->query('key');
        abort_unless(R2::conf() && preg_match('#^backups/\d{4}/\d{2}/ghani-academy-[\d-]+\.sql\.gz$#', $key), 404);

        return redirect()->away(R2::presign($key, 600));
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
