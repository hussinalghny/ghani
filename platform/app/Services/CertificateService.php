<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;

class CertificateService
{
    /** يصدر الشهادة تلقائيا لما يكمل الطالب كل الدروس */
    public function checkCompletion(User $user, Course $course): ?Certificate
    {
        $total = $course->lessons()->count();
        if ($total === 0) {
            return null;
        }
        $done = LessonProgress::query()
            ->where('user_id', $user->id)->where('course_id', $course->id)
            ->whereNotNull('completed_at')->count();

        if ($done < $total) {
            return null;
        }

        Enrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)
            ->whereNull('completed_at')->update(['completed_at' => now()]);

        if (! $course->has_certificate) {
            return null;
        }

        $existing = Certificate::query()->where('user_id', $user->id)->where('course_id', $course->id)->first();
        if ($existing) {
            return $existing;
        }

        $cert = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'code' => 'TMP-'.bin2hex(random_bytes(6)),
            'issued_at' => now(),
        ]);
        $cert->update(['code' => sprintf('GCA-%s-%04d', now()->format('Y'), $cert->id)]);

        return $cert;
    }

    public function progressPercent(User $user, Course $course): int
    {
        $total = $course->lessons()->count();
        if (! $total) {
            return 0;
        }
        $done = LessonProgress::query()->where('user_id', $user->id)->where('course_id', $course->id)
            ->whereNotNull('completed_at')->count();

        return (int) floor($done * 100 / $total);
    }
}
