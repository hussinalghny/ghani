<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\Course;
use App\Models\PaymentNotification;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * الدفع المباشر بدون بوابة رسمية:
 * الطالب يحول مبلغ مميز لمحفظتك، وتطبيق «غني باي» على موبايل أندرويد يقرأ إشعار الاستلام
 * ويرسله هنا، فنطابق المبلغ ويا الطلب ونفتح الكورس تلقائيا.
 */
class PaymentService
{
    public const HOURS = 48;

    public function __construct(private WalletService $wallet, private EnrollmentService $enrollments) {}

    /** طرق الدفع المفعلة (اللي إلها رقم بالإعدادات) */
    public static function methods(): array
    {
        $all = [
            'qi' => ['label' => 'كي كارد / سوبر كي', 'hint' => 'من تطبيق SuperQi: تحويل ← إلى رقم الهاتف'],
            'zaincash' => ['label' => 'زين كاش', 'hint' => 'من تطبيق زين كاش: تحويل أموال'],
            'fastpay' => ['label' => 'FastPay', 'hint' => 'من تطبيق FastPay: إرسال أموال'],
        ];
        $out = [];
        foreach ($all as $k => $m) {
            if ($number = setting("pay_{$k}_number")) {
                $out[$k] = $m + ['number' => $number, 'name' => setting("pay_{$k}_name")];
            }
        }

        return $out;
    }

    public static function enabled(): bool
    {
        return (bool) setting('pay_enabled') && self::methods() !== [];
    }

    public function expireOld(): void
    {
        // مهلة التفعيل الفوري خلصت وما وصل التحويل: نسجلها ضد الطالب
        PaymentOrder::where('status', 'pending')->whereNotNull('claimed_at')->where('claim_failed', false)
            ->where('claimed_at', '<', now()->subMinutes(self::instantMinutes()))->update(['claim_failed' => true]);
        PaymentOrder::where('status', 'pending')->where('expires_at', '<', now())->update(['status' => 'expired']);
    }

    /** التفعيل الفوري: الكورس ينفتح أول ما يضغط الطالب «حولت»، ويتثبت لما يوصل الإشعار */
    public static function instantMinutes(): int
    {
        return max(5, (int) (setting('pay_instant_minutes') ?: 30));
    }

    public function canClaim(PaymentOrder $order): bool
    {
        if (! $order->course_id || ! $order->isOpen() || $order->claimed_at || (string) setting('pay_instant', '1') !== '1') {
            return false;
        }
        $uid = $order->user_id;
        $failed = PaymentOrder::where('user_id', $uid)->where('claim_failed', true)->where('status', '!=', 'paid')->count();
        $active = PaymentOrder::where('user_id', $uid)->where('status', 'pending')->whereNotNull('claimed_at')->where('claim_failed', false)->exists();

        return $failed < 2 && ! $active;
    }

    public function claim(PaymentOrder $order): bool
    {
        if (! $this->canClaim($order)) {
            return false;
        }
        DB::transaction(function () use ($order) {
            $order->update(['claimed_at' => now()]);
            \App\Models\Enrollment::query()->updateOrCreate(
                ['user_id' => $order->user_id, 'course_id' => $order->course_id],
                ['source' => 'pending', 'price_paid' => 0, 'expires_at' => now()->addMinutes(self::instantMinutes())]
            );
        });

        return true;
    }

    public function create(User $user, string $method, int $base, ?Course $course = null): PaymentOrder
    {
        if (! self::enabled() || ! isset(self::methods()[$method])) {
            throw new AcademyException('الدفع المباشر غير متاح حاليا.', 'payments_disabled');
        }
        if ($base < 1000) {
            throw new AcademyException('أقل مبلغ للشحن 1,000 دينار.', 'amount_too_small');
        }
        $this->expireOld();

        // نفس الطلب المفتوح يرجع بدل ما نسوي واحد جديد
        $open = PaymentOrder::where('user_id', $user->id)->where('status', 'pending')
            ->where('method', $method)->where('base_amount', $base)->where('course_id', $course?->id)->first();
        if ($open) {
            return $open;
        }

        return DB::transaction(function () use ($user, $method, $base, $course) {
            $taken = PaymentOrder::where('status', 'pending')->lockForUpdate()->pluck('amount')->flip();
            $amount = null;
            for ($i = 0; $i < 60; $i++) {
                $try = $base + random_int(11, 499);
                if (! isset($taken[$try])) {
                    $amount = $try;
                    break;
                }
            }
            if (! $amount) {
                throw new AcademyException('ضغط على الدفع حاليا. حاول بعد دقائق.', 'busy', 429);
            }

            return PaymentOrder::create([
                'user_id' => $user->id, 'course_id' => $course?->id, 'method' => $method,
                'base_amount' => $base, 'amount' => $amount, 'expires_at' => now()->addHours(self::HOURS),
            ]);
        });
    }

    /** يطلع مبلغ الاستلام من نص الإشعار، أو null إذا مو استلام */
    public static function parse(string $text): ?array
    {
        $t = strtr($text, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.', '٬' => ',']);
        $t = preg_replace('/\s+/u', ' ', $t);

        // تحويل صادر: نتجاهله
        if (preg_match('/(من حسابك|بإرسال|بارسال|أرسلت|ارسلت|تم إرسال|تم ارسال|سحب|دفعت|sent|debited)/ui', $t)) {
            return null;
        }
        // سوبر كي: تم استلام مبلغ IQD 547,000.00 إلى حسابك بتاريخ 18/11/26 13:15:08
        if (! preg_match('/(استلام|استلمت|تم إيداع|تم ايداع|أضيف|اضيف|received|credited)/ui', $t)) {
            return null;
        }
        $clean = preg_replace('/\d{1,2}\/\d{1,2}\/\d{2,4}|\d{1,2}:\d{2}(:\d{2})?|\b07\d{9}\b|\+?9647\d{9}/', ' ', $t);
        $num = '((?:\d{1,3}(?:,\d{3})+|\d+)(?:\.\d{1,2})?)';
        // الرقم الملاصق للعملة أولا (IQD 547,000.00 أو 547,000 دينار)، وإلا أول رقم
        if (! preg_match('/IQD\s*'.$num.'/u', $clean, $m) && ! preg_match('/'.$num.'\s*(?:IQD|د\.?\s?ع|دينار)/u', $clean, $m) && ! preg_match('/'.$num.'/u', $clean, $m)) {
            return null;
        }
        $amount = (int) round((float) str_replace(',', '', $m[1]));
        $at = null;
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/', $t, $d)) {
            try {
                $at = Carbon::create(2000 + (int) $d[3], (int) $d[2], (int) $d[1], (int) $d[4], (int) $d[5], (int) $d[6], 'Asia/Baghdad');
            } catch (\Throwable) {
                $at = null;
            }
        }

        return $amount > 0 ? ['amount' => $amount, 'at' => $at] : null;
    }

    /** إشعار جاي من تطبيق غني باي */
    public function ingest(string $source, ?string $package, ?string $title, string $text, ?Carbon $postedAt = null): PaymentNotification
    {
        $postedAt ??= now();
        $fp = hash('sha256', ($package ?? $source).'|'.trim($text).'|'.$postedAt->format('Y-m-d H:i'));
        if ($existing = PaymentNotification::where('fingerprint', $fp)->first()) {
            return $existing;
        }

        $parsed = self::parse(($title ? $title.' ' : '').$text);
        $n = PaymentNotification::create([
            'fingerprint' => $fp, 'source' => $source, 'package' => $package, 'title' => $title, 'text' => mb_substr($text, 0, 2000),
            'amount' => $parsed['amount'] ?? null, 'received_at' => $parsed['at'] ?? $postedAt, 'posted_at' => $postedAt,
            'status' => $parsed ? 'review' : 'ignored',
        ]);
        if ($parsed) {
            $this->match($n);
        }

        return $n->refresh();
    }

    public function match(PaymentNotification $n): ?PaymentOrder
    {
        $this->expireOld();
        $at = $n->received_at ?? now();
        $orders = PaymentOrder::where('status', 'pending')->where('amount', $n->amount)
            ->where('created_at', '<=', $at->copy()->addMinutes(15))->get();
        if ($orders->count() !== 1) {
            return null; // ما اكو أو أكثر من واحد: مراجعة يدوية
        }

        return $this->fulfill($orders->first(), $n, 'auto');
    }

    /** تأكيد الدفع: يضيف المبلغ للمحفظة، وإذا الطلب لكورس يشتريه مباشرة */
    public function fulfill(PaymentOrder $order, ?PaymentNotification $n = null, string $by = 'admin'): ?PaymentOrder
    {
        $done = DB::transaction(function () use ($order, $n, $by) {
            $o = PaymentOrder::whereKey($order->id)->lockForUpdate()->first();
            if ($o->status === 'paid') {
                return null;
            }
            $o->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $by, 'claim_failed' => false]);
            $n?->update(['status' => 'matched', 'order_id' => $o->id]);
            $this->wallet->credit($o->user, $o->amount, 'payment', 'دفع مباشر · '.(self::methods()[$o->method]['label'] ?? $o->method), 'pay:'.$o->id);

            return $o;
        });
        if (! $done) {
            return null;
        }

        $msg = 'وصل تحويلك وانضاف '.number_format($done->amount).' د.ع لمحفظتك.';
        if ($done->course) {
            // الاشتراك المؤقت يتحول لاشتراك دائم
            \App\Models\Enrollment::where('user_id', $done->user_id)->where('course_id', $done->course_id)->where('source', 'pending')->delete();
            try {
                $this->enrollments->purchase($done->user->refresh(), $done->course);
                $msg = 'تم الدفع وانفتح كورس '.$done->course->title.'. بالتوفيق.';
            } catch (AcademyException) {
                // مشترك أصلا أو الكورس مخفي: المبلغ يبقى بالمحفظة
            }
        }
        try {
            app(FcmService::class)->toUsers([$done->user_id], 'تم الدفع', $msg, ['type' => 'payment']);
        } catch (\Throwable) {
        }

        return $done;
    }
}
