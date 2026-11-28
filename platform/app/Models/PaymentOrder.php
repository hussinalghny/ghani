<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime', 'paid_at' => 'datetime'];

    public const STATUSES = ['pending' => 'بانتظار التحويل', 'paid' => 'مدفوع', 'expired' => 'منتهي', 'cancelled' => 'ملغي'];

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(CohortRegistration::class, 'registration_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }
}
