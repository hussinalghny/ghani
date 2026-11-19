<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $guarded = ['id'];

    public const TYPES = [
        'voucher' => 'شحن بقسيمة',
        'purchase' => 'شراء كورس',
        'admin_credit' => 'إضافة من الإدارة',
        'admin_debit' => 'خصم من الإدارة',
        'voucher_issue' => 'إصدار قسائم',
        'refund' => 'استرجاع',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
