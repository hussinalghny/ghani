<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime', 'completed_at' => 'datetime'];

    public const SOURCES = ['wallet' => 'من الرصيد', 'voucher' => 'قسيمة', 'admin' => 'من الإدارة', 'free' => 'مجاني'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
