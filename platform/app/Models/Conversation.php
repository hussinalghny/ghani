<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** عنوان المحادثة من جهة الطالب */
    public function title(): string
    {
        return $this->type === 'instructor'
            ? ($this->instructor->name ?? 'المدرب')
            : 'دعم غني أكاديمي';
    }

    public function subtitle(): ?string
    {
        return $this->type === 'instructor' ? $this->course?->title : 'نرد عليك بأسرع وقت';
    }
}
