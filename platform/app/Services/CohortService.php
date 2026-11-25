<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\Cohort;
use App\Models\CohortRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** التسجيل الحضوري: حجز مقعد، ودفع بالمحفظة أو بغني باي، وتأكيد تلقائي */
class CohortService
{
    public function __construct(private WalletService $wallet) {}

    public function register(Cohort $cohort, User $user, array $data): CohortRegistration
    {
        return DB::transaction(function () use ($cohort, $user, $data) {
            Cohort::whereKey($cohort->id)->lockForUpdate()->first();
            $existing = CohortRegistration::where('cohort_id', $cohort->id)->where('user_id', $user->id)->first();
            if ($existing && in_array($existing->status, ['paid', 'pending', 'waitlist'])) {
                $existing->update(array_filter($data, fn ($v) => $v !== null));

                return $existing;
            }
            $status = $cohort->is_open && $cohort->left() > 0 ? 'pending' : 'waitlist';
            $fields = $data + ['status' => $status, 'paid_at' => null, 'paid_by' => null];
            if ($existing) {
                $existing->update($fields + ['created_at' => now()]);

                return $existing;
            }

            return CohortRegistration::create($fields + ['cohort_id' => $cohort->id, 'user_id' => $user->id, 'code' => CohortRegistration::newCode()]);
        });
    }

    /** يأكد المقعد ويخصم السعر من رصيد الطالب. يرجع false إذا الرصيد ما يكفي */
    public function confirm(CohortRegistration $reg, string $by = 'wallet'): bool
    {
        $ok = DB::transaction(function () use ($reg, $by) {
            $r = CohortRegistration::whereKey($reg->id)->lockForUpdate()->first();
            if ($r->status === 'paid') {
                return false;
            }
            $price = $r->finalPrice();
            if ($price > 0) {
                if ((int) $r->user->refresh()->balance < $price) {
                    return false;
                }
                $this->wallet->debit($r->user, $price, 'seat', 'مقعد حضوري · '.$r->cohort->title, 'seat:'.$r->id);
            }
            $r->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $by]);

            return true;
        });
        if ($ok) {
            try {
                app(FcmService::class)->toUsers([$reg->user_id], 'تم تأكيد مقعدك', 'مقعدك بكورس '.$reg->cohort->title.' الحضوري مؤكد. رمز التذكرة '.$reg->code, ['type' => 'seat']);
            } catch (\Throwable) {
            }
        }

        return $ok;
    }

    /** تأكيد يدوي من الإدارة (دفع كاش مثلا) بدون خصم من المحفظة */
    public function markPaid(CohortRegistration $reg): void
    {
        $reg->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => 'admin']);
    }

    /** يطبق كود خصم على تسجيل بانتظار الدفع، أو يشيله إذا الكود فارغ */
    public function applyDiscount(CohortRegistration $reg, ?string $code): ?string
    {
        $d = \App\Models\DiscountCode::check($code, $reg->cohort, $reg->id);
        if (is_string($d)) {
            return $d;
        }
        $reg->update(['discount_code_id' => $d?->id, 'price' => $d ? $d->apply($reg->cohort->price) : null]);
        // طلبات الدفع المفتوحة بالسعر القديم تنلغي حتى ما يحول مبلغ غلط
        \App\Models\PaymentOrder::where('registration_id', $reg->id)->where('status', 'pending')->update(['status' => 'cancelled']);

        return null;
    }

    public function payFromWallet(CohortRegistration $reg): void
    {
        if ($reg->status === 'waitlist') {
            throw new AcademyException('المقاعد اكتملت. أنت بقائمة الانتظار ونتواصل وياك إذا توفر مقعد.', 'full');
        }
        if (! $this->confirm($reg, 'wallet')) {
            throw new AcademyException('رصيدك ما يكفي. ادفع بالتحويل المباشر.', 'insufficient_balance');
        }
    }
}
