<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['last_seen_at' => 'datetime', 'is_blocked' => 'boolean'];

    public const PLATFORMS = ['web' => 'متصفح', 'android' => 'Android', 'ios' => 'iPhone'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
