<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\Lesson;
use App\Models\LessonView;
use App\Models\User;

/** يجمع كل قواعد الوصول للدرس ويولد بيانات التشغيل المحمية */
class LessonAccess
{
    public function __construct(private BunnyStream $bunny) {}

    public function authorize(?User $user, Lesson $lesson): void
    {
        $course = $lesson->course;
        if (! $course->is_published && ! $user?->isAdmin()) {
            throw new AcademyException('الكورس غير متاح.', 'unavailable', 404);
        }
        if ($lesson->is_free_preview) {
            return;
        }
        if (! $user) {
            throw new AcademyException('سجل دخولك لمشاهدة الدرس.', 'unauthenticated', 401);
        }
        if (! $user->hasAccessTo($course)) {
            throw new AcademyException('اشترك بالكورس حتى تفتح هذا الدرس.', 'not_enrolled', 403);
        }
    }

    /** حد أعلى لفتح الدروس بالساعة: يكشف السحب الآلي للفيديوهات */
    public function throttle(User $user, Lesson $lesson, ?int $deviceId, ?string $ip): void
    {
        if ($user->isAdmin()) {
            return;
        }
        $limit = (int) setting('lesson_opens_per_hour');
        $recent = LessonView::query()->where('user_id', $user->id)->where('created_at', '>', now()->subHour())->count();
        if ($limit > 0 && $recent >= $limit) {
            throw new AcademyException('فتحت دروس كثيرة خلال وقت قصير. ارجع بعد شوي.', 'too_many_opens', 429);
        }
        LessonView::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'device_id' => $deviceId, 'ip' => $ip]);
    }

    public function playback(Lesson $lesson, ?User $user): array
    {
        $data = [
            'type' => $lesson->type,
            'embed_url' => null,
            'hls_url' => null,
            'expires_in' => config('academy.bunny.token_ttl') * 60,
            'watermark' => null,
        ];
        if ($lesson->type === 'video' && $lesson->bunny_video_id) {
            $data['embed_url'] = $this->bunny->embedUrl($lesson->bunny_video_id);
            $data['hls_url'] = $this->bunny->hlsUrl($lesson->bunny_video_id);
        }
        if ($user && setting('watermark')) {
            $data['watermark'] = $user->watermarkText();
        }

        return $data;
    }
}
