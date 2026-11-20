<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use App\Services\EnrollmentService;
use App\Services\LessonAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class LearnController extends Controller
{
    public function __construct(private LessonAccess $access, private CertificateService $certs) {}

    public function myCourses(Request $request)
    {
        $user = $request->user();
        $enrollments = $user->enrollments()->with(['course' => fn ($q) => $q->withCount('lessons')->with(['category', 'instructor'])])->latest()->get();
        $done = LessonProgress::where('user_id', $user->id)->whereNotNull('completed_at')
            ->selectRaw('course_id, count(*) as c')->groupBy('course_id')->pluck('c', 'course_id');

        return response()->json(['data' => $enrollments->filter(fn ($e) => $e->course)->values()->map(fn ($e) => [
            'course' => new CourseResource($e->course),
            'progress' => $e->course->lessons_count ? (int) floor(($done[$e->course_id] ?? 0) * 100 / $e->course->lessons_count) : 0,
            'expires_at' => $e->expires_at?->toIso8601String(),
            'completed_at' => $e->completed_at?->toIso8601String(),
        ])]);
    }

    public function enroll(Request $request, Course $course, EnrollmentService $service)
    {
        $service->purchase($request->user(), $course);

        return response()->json(['message' => 'تم تسجيلك في '.$course->title.'.', 'balance' => $request->user()->balance]);
    }

    public function curriculum(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($user->hasAccessTo($course), 403, 'اشترك بالكورس أولا.');
        $progress = LessonProgress::where('user_id', $user->id)->where('course_id', $course->id)->get()->keyBy('lesson_id');

        return response()->json([
            'progress' => $this->certs->progressPercent($user, $course),
            'sections' => $course->sections()->with('lessons')->get()->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'lessons' => $s->lessons->map(fn ($l) => [
                    'id' => $l->id,
                    'title' => $l->title,
                    'type' => $l->type,
                    'duration' => $l->duration,
                    'completed' => (bool) optional($progress->get($l->id))->completed_at,
                    'position' => (int) optional($progress->get($l->id))->position,
                ]),
            ]),
        ]);
    }

    /** بيانات تشغيل الدرس: روابط موقعة تنتهي بعد مدة + نص العلامة المائية */
    public function lesson(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $this->access->authorize($user, $lesson);
        $this->access->throttle($user, $lesson, $request->attributes->get('device')?->id, $request->ip());

        $progress = LessonProgress::where('user_id', $user->id)->where('lesson_id', $lesson->id)->first();

        return response()->json(['data' => [
            'id' => $lesson->id,
            'course_id' => $lesson->course_id,
            'title' => $lesson->title,
            'type' => $lesson->type,
            'duration' => $lesson->duration,
            'body' => $lesson->body,
            'position' => (int) $progress?->position,
            'completed' => (bool) $progress?->completed_at,
            'playback' => $this->access->playback($lesson, $user, $request->header('X-Client') === 'desktop' ? 'desktop' : 'app'),
            'attachment' => $lesson->attachment ? [
                'name' => $lesson->attachment_name,
                'url' => URL::temporarySignedRoute('learn.file', now()->addMinutes(10), ['lesson' => $lesson->id, 'u' => $user->id]),
            ] : null,
        ]]);
    }

    public function progress(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        abort_unless($user->hasAccessTo($lesson->course), 403);
        $data = $request->validate(['position' => ['nullable', 'integer', 'min:0'], 'completed' => ['nullable', 'boolean']]);

        $row = LessonProgress::firstOrNew(['user_id' => $user->id, 'lesson_id' => $lesson->id]);
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
            'progress' => $this->certs->progressPercent($user, $lesson->course),
            'certificate' => $cert ? ['code' => $cert->code, 'url' => route('certificates.show', $cert)] : null,
        ]);
    }
}
