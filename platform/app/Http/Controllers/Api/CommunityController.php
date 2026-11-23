<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Services\CertificateService;
use App\Services\Community;
use Illuminate\Http\Request;

/** تقييمات الكورسات وأسئلة الدروس */
class CommunityController extends Controller
{
    public function reviews(Course $course)
    {
        abort_unless($course->is_published, 404);
        $list = $course->reviews()->where('is_visible', true)->with('user:id,name')->latest()->paginate(20);

        return response()->json([
            'rating' => round((float) $course->rating_avg, 1),
            'rating_count' => (int) $course->rating_count,
            'breakdown' => Community::breakdown($course),
            'data' => $list->getCollection()->map->toApi(),
            'has_more' => $list->hasMorePages(),
        ]);
    }

    public function review(Request $request, Course $course, CertificateService $certs)
    {
        $user = $request->user();
        abort_unless($user->hasAccessTo($course), 403, 'اشترك بالكورس أولا.');
        abort_if($certs->progressPercent($user, $course) < Community::REVIEW_MIN_PROGRESS, 422,
            'تقدر تقيم الكورس بعد ما تكمل '.Community::REVIEW_MIN_PROGRESS.'% منه.');
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'body' => ['nullable', 'string', 'max:1000']]);

        $r = CourseReview::updateOrCreate(['course_id' => $course->id, 'user_id' => $user->id], $data);
        CourseReview::syncRating($course);

        return response()->json(['message' => 'شكرا. تقييمك وصل.', 'data' => $r->load('user:id,name')->toApi()]);
    }

    public function questions(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        abort_unless($user->hasAccessTo($lesson->course), 403, 'اشترك بالكورس أولا.');

        $list = LessonQuestion::where('lesson_id', $lesson->id)
            ->where(fn ($q) => $q->whereNotNull('answered_at')->orWhere('user_id', $user->id))
            ->with('user:id,name')->latest()->take(50)->get();

        return response()->json(['data' => $list->map->toApi($user)]);
    }

    public function ask(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        abort_unless($user->hasAccessTo($lesson->course), 403, 'اشترك بالكورس أولا.');
        $data = $request->validate(['body' => ['required', 'string', 'min:5', 'max:1500']]);

        $q = LessonQuestion::create(['lesson_id' => $lesson->id, 'course_id' => $lesson->course_id, 'user_id' => $user->id, 'body' => $data['body']]);
        Community::notifyInstructor($q);

        return response()->json(['message' => 'وصل سؤالك للمدرب. راح يوصلك إشعار لما يجاوب.', 'data' => $q->load('user:id,name')->toApi($user)]);
    }
}
