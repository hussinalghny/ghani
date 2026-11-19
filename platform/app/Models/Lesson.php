<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_free_preview' => 'boolean', 'duration' => 'integer'];

    public const TYPES = ['video' => 'فيديو', 'text' => 'مقال', 'file' => 'ملف'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function durationLabel(): string
    {
        if (! $this->duration) {
            return '';
        }
        $m = intdiv($this->duration, 60);
        $s = $this->duration % 60;

        return sprintf('%d:%02d', $m, $s);
    }
}
