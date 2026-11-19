<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\Course;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Illuminate\Support\Facades\DB;

class VoucherService
{
    // بدون الأحرف المتشابهة (0 O 1 I L)
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    private const LENGTH = 12;

    public function __construct(private WalletService $wallet, private EnrollmentService $enrollments) {}

    public static function normalize(string $code): string
    {
        // يقبل الأرقام العربية والمسافات والشرطات
        $code = strtr($code, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);

        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    private function randomCode(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * إصدار دفعة قسائم.
     * $resellerPays = true: يخصم الكلفة من رصيد الموزع حسب نسبة خصمه.
     */
    public function issueBatch(array $data, User $creator, ?User $reseller = null, bool $resellerPays = false): VoucherBatch
    {
        $amount = (int) $data['amount'];
        $qty = (int) $data['quantity'];
        $course = ! empty($data['course_id']) ? Course::find($data['course_id']) : null;
        if ($course) {
            $amount = $course->finalPrice();
        }

        return DB::transaction(function () use ($data, $creator, $reseller, $resellerPays, $amount, $qty, $course) {
            $cost = 0;
            if ($reseller && $resellerPays) {
                $cost = (int) round($amount * $qty * (100 - $reseller->reseller_discount) / 100);
                $this->wallet->debit($reseller, $cost, 'voucher_issue', "إصدار $qty قسيمة بقيمة ".iqd($amount));
            }

            $batch = VoucherBatch::create([
                'name' => $data['name'] ?? ('دفعة '.iqd($amount).' × '.$qty),
                'amount' => $amount,
                'quantity' => $qty,
                'course_id' => $course?->id,
                'reseller_id' => $reseller?->id,
                'created_by' => $creator->id,
                'cost' => $cost,
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            $rows = [];
            $codes = [];
            while (count($codes) < $qty) {
                $c = $this->randomCode();
                $codes[$c] = true;
            }
            $existing = Voucher::query()->whereIn('code', array_keys($codes))->pluck('code')->all();
            foreach ($existing as $dup) {
                unset($codes[$dup]);
                do {
                    $c = $this->randomCode();
                } while (isset($codes[$c]) || Voucher::where('code', $c)->exists());
                $codes[$c] = true;
            }

            $now = now();
            foreach (array_keys($codes) as $code) {
                $rows[] = [
                    'batch_id' => $batch->id,
                    'code' => $code,
                    'amount' => $amount,
                    'course_id' => $course?->id,
                    'reseller_id' => $reseller?->id,
                    'status' => 'active',
                    'expires_at' => $batch->expires_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                Voucher::insert($chunk);
            }

            return $batch;
        });
    }

    /**
     * استخدام قسيمة: إما تشحن الرصيد أو تفتح كورس محدد.
     * @return array{type:string, amount:int, course:?Course}
     */
    public function redeem(User $user, string $rawCode): array
    {
        $code = self::normalize($rawCode);
        if (strlen($code) < 8) {
            throw new AcademyException('اكتب كود القسيمة كاملا.', 'invalid_code');
        }

        return DB::transaction(function () use ($user, $code) {
            $voucher = Voucher::query()->where('code', $code)->lockForUpdate()->first();

            if (! $voucher) {
                throw new AcademyException('الكود غير صحيح. تأكد من الأحرف وأعد المحاولة.', 'invalid_code');
            }
            if ($voucher->status === 'redeemed') {
                throw new AcademyException('هذه القسيمة مستخدمة سابقا.', 'used_code');
            }
            if ($voucher->status === 'disabled') {
                throw new AcademyException('هذه القسيمة معطلة. تواصل مع الجهة التي باعتها لك.', 'disabled_code');
            }
            if ($voucher->expires_at && $voucher->expires_at->isPast()) {
                throw new AcademyException('انتهت صلاحية هذه القسيمة.', 'expired_code');
            }

            $course = null;
            if ($voucher->course_id) {
                $course = Course::findOrFail($voucher->course_id);
                if ($user->hasAccessTo($course)) {
                    throw new AcademyException('أنت مشترك في هذا الكورس. احتفظ بالقسيمة لشخص آخر.', 'already_enrolled');
                }
                $this->enrollments->enroll($user, $course, 'voucher', $voucher->amount);
            } else {
                $this->wallet->credit($user, $voucher->amount, 'voucher', 'شحن بقسيمة '.$voucher->formatted(), 'voucher:'.$voucher->id);
            }

            $voucher->forceFill([
                'status' => 'redeemed',
                'redeemed_by' => $user->id,
                'redeemed_at' => now(),
                'sold_at' => $voucher->sold_at ?? now(),
            ])->save();

            return ['type' => $course ? 'course' : 'balance', 'amount' => $voucher->amount, 'course' => $course];
        });
    }
}
