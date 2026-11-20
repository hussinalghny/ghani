<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentNotification extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['received_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'order_id');
    }
}
