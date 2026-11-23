<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\LessonQuestion;

class Community
{
    /** أقل نسبة إكمال حتى يقدر الطالب يقيم (حتى تكون التقييمات حقيقية) */
    public const REVIEW_MIN_PROGRESS = 30;

    public static function breakdown(Course $course): array
    {
        $rows = CourseReview::where('course_id', $course->id)->where('is_visible', true)
            ->selectRaw('rating, count(*) as c')->groupBy('rating')->pluck('c', 'rating');
        $total = max(1, $rows->sum());

        return collect([5, 4, 3, 2, 1])->map(fn ($s) => ['stars' => $s, 'percent' => (int) round(($rows[$s] ?? 0) * 100 / $total)])->all();
    }

    public static function notifyInstructor(LessonQuestion $q): void
    {
        try {
            $uid = $q->course?->instructor?->user_id;
            if ($uid) {
                app(FcmService::class)->toUsers([$uid], 'سؤال جديد على '.$q->lesson->title, \Illuminate\Support\Str::limit($q->body, 90), ['type' => 'question']);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function notifyStudent(LessonQuestion $q): void
    {
        try {
            app(FcmService::class)->toUsers([$q->user_id], 'المدرب جاوب سؤالك', \Illuminate\Support\Str::limit($q->answer, 90), ['type' => 'answer', 'lesson_id' => (string) $q->lesson_id]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
