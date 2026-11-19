<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sold_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const STATUSES = ['active' => 'فعالة', 'redeemed' => 'مستخدمة', 'disabled' => 'معطلة'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    /** يعرض الكود بشكل مقروء: GA7K-29XQ-MP4D */
    public function formatted(): string
    {
        return implode('-', str_split($this->code, 4));
    }

    public function statusLabel(): string
    {
        if ($this->status === 'active' && $this->expires_at && $this->expires_at->isPast()) {
            return 'منتهية';
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }
}
