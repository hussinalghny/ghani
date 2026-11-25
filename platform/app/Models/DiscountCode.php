<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** كود خصم للتسجيل الحضوري: نسبة أو مبلغ ثابت، بعدد استخدامات وتاريخ انتهاء */
class DiscountCode extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime', 'is_active' => 'boolean', 'value' => 'integer', 'max_uses' => 'integer'];

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CohortRegistration::class);
    }

    /** الاستخدامات: المؤكدة + المحجوزة اللي بعدها بمهلة الدفع */
    public function uses(?int $exceptRegistration = null): int
    {
        return $this->registrations()->when($exceptRegistration, fn ($q) => $q->where('id', '!=', $exceptRegistration))
            ->where(fn ($q) => $q->where('status', 'paid')
                ->orWhere(fn ($q) => $q->where('status', 'pending')->where('created_at', '>', now()->subHours(\App\Services\PaymentService::HOURS))))->count();
    }

    public function label(): string
    {
        return $this->type === 'percent' ? $this->value.'%' : number_format($this->value).' د.ع';
    }

    public function apply(int $price): int
    {
        $off = $this->type === 'percent' ? (int) round($price * min(100, $this->value) / 100) : $this->value;

        return max(0, $price - $off);
    }

    /** يرجع الكود إذا صالح لهالدفعة، أو رسالة الخطأ */
    public static function check(?string $code, Cohort $cohort, ?int $exceptRegistration = null): self|string|null
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }
        $d = static::where('code', $code)->first();
        if (! $d || ! $d->is_active || ($d->cohort_id && $d->cohort_id !== $cohort->id)) {
            return 'كود الخصم غير صحيح.';
        }
        if ($d->expires_at && $d->expires_at->isPast()) {
            return 'كود الخصم منتهي.';
        }
        if ($d->max_uses !== null && $d->uses($exceptRegistration) >= $d->max_uses) {
            return 'كود الخصم انتهت استخداماته.';
        }

        return $d;
    }
}
