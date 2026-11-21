<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\Course;
use App\Models\PaymentNotification;
use App\Models\PaymentOrder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * الدفع المباشر بدون بوابة رسمية:
 * الطالب يحول مبلغ مميز لمحفظتك، وتطبيق «غني باي» على موبايل أندرويد يقرأ إشعار الاستلام
 * ويرسله هنا، فنطابق المبلغ ويا الطلب ونفتح الكورس تلقائيا.
 */
class PaymentService
{
    public const HOURS = 48;

    /** بعد كم دقيقة بدون نبض نعتبر الموبايل منقطع */
    public const OFFLINE_MIN = 10;

    /** أقل فرق رصيد نعتبره إشعار ضايع */
    public const GAP_MIN = 1000;

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
        PaymentOrder::where('status', 'pending')->where('expires_at', '<', now())->update(['status' => 'expired']);
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

    private static function digits(string $text): string
    {
        $t = strtr($text, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.', '٬' => ',']);

        return preg_replace('/\s+/u', ' ', $t);
    }

    /** رصيد المحفظة بعد العملية (سوبر كي: رصيد حسابك الحالي 1,094,319)، أو null */
    public static function balance(string $text): ?int
    {
        if (! preg_match('/(?:رصيد|الرصيد|balance)[^0-9]{0,30}?((?:\d{1,3}(?:,\d{3})+|\d+)(?:\.\d{1,2})?)/ui', self::digits($text), $m)) {
            return null;
        }

        return (int) round((float) str_replace(',', '', $m[1]));
    }

    /** يطلع مبلغ الاستلام من نص الإشعار، أو null إذا مو استلام */
    public static function parse(string $text): ?array
    {
        $t = self::digits($text);

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

        $full = ($title ? $title.' ' : '').$text;
        $parsed = self::parse($full);
        $n = PaymentNotification::create([
            'fingerprint' => $fp, 'source' => $source, 'package' => $package, 'title' => $title, 'text' => mb_substr($text, 0, 2000),
            'amount' => $parsed['amount'] ?? null, 'balance' => self::balance($full), 'received_at' => $parsed['at'] ?? $postedAt,
            'status' => $parsed ? 'review' : 'ignored',
        ]);
        if ($parsed && ! $this->settleGaps($n)) {
            $this->match($n);
        }
        if ($n->balance !== null) {
            $this->checkBalance($n->refresh());
        }

        return $n->refresh();
    }

    /**
     * إشعار وصل متأخر ووقته داخل فرق رصيد مسجل: ينقص الفرق.
     * إذا الفرق انربط بطلب قبل، الإشعار ينحسب مكرر ونتجاهله (حتى ما ينضاف مرتين).
     */
    private function settleGaps(PaymentNotification $n): bool
    {
        $at = $n->received_at;
        $gaps = PaymentNotification::where('source', 'gap')->where('package', $n->package)->whereIn('status', ['review', 'matched'])
            ->where('gap_from', '<', $at)->where('received_at', '>=', $at)->orderBy('id')->get();
        foreach ($gaps as $g) {
            if ($g->status === 'matched') {
                if ((int) $g->amount === (int) $n->amount) {
                    $n->update(['status' => 'ignored', 'order_id' => $g->order_id, 'title' => 'محسوب سابقا من فرق الرصيد #'.$g->id]);

                    return true;
                }

                continue;
            }
            $left = (int) $g->amount - (int) $n->amount;
            $g->update($left < self::GAP_MIN ? ['status' => 'ignored', 'title' => 'انحل: وصل إشعاره متأخر'] : ['amount' => $left]);

            return false;
        }

        return false;
    }

    /** يقارن الرصيد ويا الإشعار اللي قبله: إذا الرصيد زاد أكثر من المستلم، اكو تحويل ضاع إشعاره */
    private function checkBalance(PaymentNotification $n): void
    {
        $prev = PaymentNotification::whereKeyNot($n->id)->where('source', '!=', 'gap')->where('package', $n->package)
            ->whereNotNull('balance')->where('received_at', '<=', $n->received_at)
            ->orderByDesc('received_at')->orderByDesc('id')->first();
        if (! $prev) {
            return;
        }
        $gap = (int) $n->balance - (int) $prev->balance - (int) $n->amount;
        if ($gap < self::GAP_MIN) {
            return;
        }
        $tz = 'Asia/Baghdad';
        $from = $prev->received_at->timezone($tz)->format('m/d H:i');
        $to = $n->received_at->timezone($tz)->format('m/d H:i');
        $g = PaymentNotification::firstOrCreate(['fingerprint' => hash('sha256', 'gap|'.$n->id)], [
            'source' => 'gap', 'package' => $n->package, 'title' => 'فرق رصيد',
            'text' => 'الرصيد صار '.number_format($n->balance).' بعد ما كان '.number_format($prev->balance).'، والمستلم '.number_format((int) $n->amount)
                .'. يعني اكو تحويل بقيمة '.number_format($gap).' وصل بين '.$from.' و '.$to.' وما وصل إشعاره.',
            'amount' => $gap, 'status' => 'review', 'received_at' => $n->received_at, 'gap_from' => $prev->received_at,
        ]);
        if ($g->wasRecentlyCreated) {
            $this->alert('⚠️ غني باي: فرق رصيد '.number_format($gap).' د.ع بين '.$from.' و '.$to.'. اكو تحويل ما وصل إشعاره، راجعه بقائمة المراجعة.');
        }
    }

    // ---------- نبض الموبايل والتنبيهات ----------

    /** true متصل، false منقطع، null بعده ما ربطته */
    public static function deviceOnline(): ?bool
    {
        $seen = setting('pay_device_seen');

        return $seen ? Carbon::parse($seen)->gt(now()->subMinutes(self::OFFLINE_MIN)) : null;
    }

    public static function device(): array
    {
        return (array) (json_decode((string) setting('pay_device'), true) ?: []);
    }

    public function heartbeat(array $info): void
    {
        $down = setting('pay_alert_down');
        $lowBattery = isset($info['battery']) && $info['battery'] <= 15 && empty($info['charging']);
        $put = ['pay_device_seen' => now()->toDateTimeString(), 'pay_device' => json_encode($info), 'pay_alert_down' => ''];
        if ($lowBattery && ! setting('pay_alert_battery')) {
            $put['pay_alert_battery'] = 1;
            $this->alert('🔋 بطارية موبايل غني باي '.$info['battery'].'%. اشحنه حتى ما ينطفي.');
        } elseif (! $lowBattery) {
            $put['pay_alert_battery'] = '';
        }
        Setting::put($put);
        if ($down) {
            $this->alert('✅ رجع اتصال غني باي (كان منقطع من '.Carbon::parse($down)->timezone('Asia/Baghdad')->format('H:i').').');
        }
    }

    /** إذا الموبايل انقطع: تنبيه واحد بس لحد ما يرجع */
    public function watchdog(): bool
    {
        if (! self::enabled() || self::deviceOnline() !== false || setting('pay_alert_down')) {
            return false;
        }
        $seen = (string) setting('pay_device_seen');
        Setting::put(['pay_alert_down' => $seen]);
        $pending = PaymentOrder::where('status', 'pending')->count();
        $this->alert('🚨 غني باي منقطع من '.Carbon::parse($seen)->timezone('Asia/Baghdad')->format('H:i')
            .'. الدفعات ما راح تتأكد تلقائيا. شيك الموبايل والنت. الطلبات المعلقة: '.$pending);

        return true;
    }

    /** نفس watchdog بس مرة بالدقيقة كحد أقصى (للصفحات اللي تنفتح كثير) */
    public function watchdogLazy(): void
    {
        if (Cache::add('pay.watchdog', 1, 60)) {
            rescue(fn () => $this->watchdog(), report: false);
        }
    }

    /** رسالة تيليگرام للإدارة (إذا مضبوط البوت) */
    public function alert(string $text): bool
    {
        $token = (string) setting('pay_tg_token');
        $chat = (string) setting('pay_tg_chat');
        if ($token === '' || $chat === '') {
            return false;
        }
        try {
            return Http::asForm()->timeout(6)->post("https://api.telegram.org/bot{$token}/sendMessage", ['chat_id' => $chat, 'text' => $text])->successful();
        } catch (\Throwable) {
            return false;
        }
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
            $o->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $by]);
            $n?->update(['status' => 'matched', 'order_id' => $o->id]);
            $this->wallet->credit($o->user, $o->amount, 'payment', 'دفع مباشر · '.(self::methods()[$o->method]['label'] ?? $o->method), 'pay:'.$o->id);

            return $o;
        });
        if (! $done) {
            return null;
        }

        $msg = 'وصل تحويلك وانضاف '.number_format($done->amount).' د.ع لمحفظتك.';
        if ($done->course) {
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
