<?php

namespace App\Http\Controllers;

use App\Exceptions\AcademyException;
use App\Models\Course;
use App\Models\Device;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use App\Services\LessonAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class LearnController extends Controller
{
    public function __construct(private LessonAccess $access, private CertificateService $certs) {}

    public function show(Request $request, Course $course, ?Lesson $lesson = null)
    {
        $user = $request->user();
        $lessons = $course->orderedLessons();
        abort_if($lessons->isEmpty(), 404, 'لا توجد دروس بعد.');

        $done = LessonProgress::query()->where('user_id', $user->id)->where('course_id', $course->id)
            ->get()->keyBy('lesson_id');

        if (! $lesson) {
            $lesson = $lessons->first(fn ($l) => ! optional($done->get($l->id))->completed_at) ?? $lessons->first();
        }
        abort_unless($lesson->course_id === $course->id, 404);

        try {
            $this->access->authorize($user, $lesson);
        } catch (AcademyException $e) {
            return redirect()->route('courses.show', $course)->with('error', $e->getMessage());
        }

        $device = Device::query()->where('user_id', $user->id)->where('device_id', (string) $this->webDeviceId())->first();
        $this->access->throttle($user, $lesson, $device?->id, $request->ip());

        $playback = $this->access->playback($lesson, $user);
        $fileUrl = $lesson->attachment
            ? URL::temporarySignedRoute('learn.file', now()->addMinutes(10), ['lesson' => $lesson->id, 'u' => $user->id])
            : null;

        $course->load('sections');
        $index = $lessons->search(fn ($l) => $l->id === $lesson->id);
        $prev = $index > 0 ? $lessons[$index - 1] : null;
        $next = $lessons[$index + 1] ?? null;
        $enrolled = $user->hasAccessTo($course);
        $progress = $enrolled ? $this->certs->progressPercent($user, $course) : 0;

        return view('learn.show', compact('course', 'lesson', 'lessons', 'done', 'playback', 'fileUrl', 'prev', 'next', 'enrolled', 'progress'));
    }

    public function progress(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $data = $request->validate([
            'position' => ['nullable', 'integer', 'min:0'],
            'completed' => ['nullable', 'boolean'],
        ]);
        abort_unless($user->hasAccessTo($lesson->course), 403);

        $row = LessonProgress::query()->firstOrNew(['user_id' => $user->id, 'lesson_id' => $lesson->id]);
        $row->course_id = $lesson->course_id;
        if (isset($data['position'])) {
            $row->position = $data['position'];
        }
        if (! empty($data['completed']) && ! $row->completed_at) {
            $row->completed_at = now();
        }
        $row->save();

        $cert = $row->completed_at ? $this->certs->checkCompletion($user, $lesson->course) : null;

        return response()->json([
            'ok' => true,
            'progress' => $this->certs->progressPercent($user, $lesson->course),
            'certificate' => $cert ? route('certificates.show', $cert) : null,
        ]);
    }

    public function download(Request $request, Lesson $lesson)
    {
        $user = \App\Models\User::find($request->query('u'));
        abort_unless($user && $user->is_active, 403);
        $this->access->authorize($user, $lesson);
        abort_unless($lesson->attachment && Storage::disk('local')->exists($lesson->attachment), 404);

        return Storage::disk('local')->download($lesson->attachment, $lesson->attachment_name ?: basename($lesson->attachment), [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
