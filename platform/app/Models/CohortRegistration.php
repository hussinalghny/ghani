<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CohortRegistration extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['paid_at' => 'datetime', 'checked_in_at' => 'datetime'];

    public const STATUSES = ['pending' => 'بانتظار الدفع', 'paid' => 'مؤكد', 'waitlist' => 'قائمة الانتظار', 'cancelled' => 'ملغي'];

    public const LEVELS = ['none' => 'مبتدئ تماما', 'basic' => 'عندي أساسيات', 'good' => 'عندي خبرة'];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class, 'discount_code_id');
    }

    /** السعر النهائي لهالتسجيل (بعد الخصم إن وجد) */
    public function finalPrice(): int
    {
        return $this->price ?? $this->cohort->price;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function newCode(): string
    {
        do {
            $c = 'GA-'.strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('code', $c)->exists());

        return strtr($c, ['0' => '7', 'O' => 'Q', 'I' => 'K', 'L' => 'M', '1' => '8']);
    }
}
