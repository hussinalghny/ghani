<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** كود خصم للحضوري أو الكورسات الإلكترونية أو الكل: نسبة أو مبلغ ثابت، بعدد استخدامات وتاريخ انتهاء */
class DiscountCode extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime', 'is_active' => 'boolean', 'value' => 'integer', 'max_uses' => 'integer'];

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public const SCOPES = ['cohort' => 'الحضوري', 'course' => 'الإلكتروني', 'all' => 'الكل'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    /** على شنو ينطبق، بكلام مفهوم */
    public function target(): string
    {
        return match (true) {
            (bool) $this->cohort_id => 'حضوري · '.($this->cohort?->title ?? '—'),
            (bool) $this->course_id => 'كورس · '.($this->course?->title ?? '—'),
            $this->applies_to === 'all' => 'كل الكورسات والحضوري',
            $this->applies_to === 'course' => 'كل الكورسات الإلكترونية',
            default => 'كل الكورسات الحضورية',
        };
    }

    /** مبيعات الكود: الاشتراكات الإلكترونية + المقاعد المؤكدة */
    public function revenue(): int
    {
        return (int) $this->enrollments()->sum('price_paid')
            + (int) $this->registrations()->where('status', 'paid')->with('cohort')->get()->sum(fn ($r) => $r->finalPrice());
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CohortRegistration::class);
    }

    /** الاستخدامات: المقاعد المؤكدة والمحجوزة بمهلة الدفع + الاشتراكات + طلبات الدفع المفتوحة للكورسات */
    public function uses(?int $exceptRegistration = null, ?int $exceptUser = null): int
    {
        $hours = \App\Services\PaymentService::HOURS;

        return $this->registrations()->when($exceptRegistration, fn ($q) => $q->where('id', '!=', $exceptRegistration))
            ->where(fn ($q) => $q->where('status', 'paid')
                ->orWhere(fn ($q) => $q->where('status', 'pending')->where('created_at', '>', now()->subHours($hours))))->count()
            + $this->enrollments()->count()
            + $this->orders()->whereNotNull('course_id')->where('status', 'pending')->where('expires_at', '>', now())
                ->when($exceptUser, fn ($q) => $q->where('user_id', '!=', $exceptUser))->count();
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
    public static function check(?string $code, Cohort|Course $target, ?int $exceptRegistration = null, ?int $exceptUser = null): self|string|null
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }
        $d = static::where('code', $code)->first();
        if (! $d || ! $d->is_active || ! $d->fits($target)) {
            return 'كود الخصم غير صحيح.';
        }
        if ($d->expires_at && $d->expires_at->isPast()) {
            return 'كود الخصم منتهي.';
        }
        if ($d->max_uses !== null && $d->uses($exceptRegistration, $exceptUser) >= $d->max_uses) {
            return 'كود الخصم انتهت استخداماته.';
        }

        return $d;
    }

    /** الكود ينفع لهذا الحضوري أو الكورس؟ */
    public function fits(Cohort|Course $target): bool
    {
        if ($target instanceof Cohort) {
            return in_array($this->applies_to, ['cohort', 'all'], true) && ! $this->course_id
                && (! $this->cohort_id || $this->cohort_id === $target->id);
        }

        return in_array($this->applies_to, ['course', 'all'], true) && ! $this->cohort_id
            && (! $this->course_id || $this->course_id === $target->id);
    }
}
