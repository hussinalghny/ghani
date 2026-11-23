<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseReview extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_visible' => 'boolean', 'rating' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** يحدث متوسط التقييم وعدده بالكورس (حتى ما نحسبه بكل طلب) */
    public static function syncRating(Course $course): void
    {
        $q = static::where('course_id', $course->id)->where('is_visible', true);
        $course->forceFill(['rating_avg' => round((float) $q->avg('rating'), 2), 'rating_count' => $q->count()])->saveQuietly();
        cache()->forget('api.home');
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'body' => $this->body,
            'name' => $this->user ? \Illuminate\Support\Str::of($this->user->name)->explode(' ')->first() : 'طالب',
            'date' => $this->created_at?->toDateString(),
        ];
    }
}
