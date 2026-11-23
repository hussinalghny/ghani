<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonQuestion extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['answered_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function toApi(?User $viewer = null): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'name' => $this->user ? \Illuminate\Support\Str::of($this->user->name)->explode(' ')->first() : 'طالب',
            'mine' => $viewer && $viewer->id === $this->user_id,
            'answer' => $this->answer,
            'answered_at' => $this->answered_at?->toIso8601String(),
            'date' => $this->created_at?->toDateString(),
        ];
    }
}
