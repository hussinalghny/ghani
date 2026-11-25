<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** دفعة حضورية لكورس: مقاعد محدودة، موعد، مكان، وتسجيل بالدفع المباشر */
class Cohort extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['details' => 'array', 'is_open' => 'boolean', 'starts_on' => 'date', 'price' => 'integer', 'seats' => 'integer'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CohortRegistration::class);
    }

    /** المقاعد المحجوزة: المدفوعة + طلبات الدفع المفتوحة (آخر 48 ساعة) */
    public function taken(): int
    {
        return $this->registrations()->where(fn ($q) => $q->where('status', 'paid')
            ->orWhere(fn ($q) => $q->where('status', 'pending')->where('created_at', '>', now()->subHours(\App\Services\PaymentService::HOURS))))->count();
    }

    public function paidCount(): int
    {
        return $this->registrations()->where('status', 'paid')->count();
    }

    public function left(): int
    {
        return max(0, $this->seats - $this->taken());
    }

    public function d(string $key): array
    {
        return array_values(array_filter((array) (($this->details ?? [])[$key] ?? [])));
    }

    public function imageUrl(): ?string
    {
        if ($this->image) {
            return str_starts_with($this->image, 'img/') ? asset($this->image) : asset('storage/'.$this->image);
        }

        return $this->course?->coverUrl();
    }

    public static function defaultDetails(): array
    {
        return [
            'outcomes' => [
                'تفهم شلون يفكر المهاجم وأشهر أنواع الهجمات اليوم',
                'تبني مختبر أمن خاص بيك وتتدرب عليه بأمان',
                'تستخدم أدوات الذكاء الاصطناعي لكشف التهديدات وتحليلها',
                'تفحص المواقع والشبكات وتكتب تقرير ثغرات احترافي',
                'تتعامل مع حادثة أمنية خطوة بخطوة',
                'تطلع بمشروع حقيقي تضيفه لسيرتك الذاتية',
            ],
            'weeks' => [
                'الأسبوع الأول | أساسيات الأمن السيبراني وطريقة تفكير المهاجم، وبناء المختبر',
                'الأسبوع الثاني | فحص الشبكات والمواقع واكتشاف الثغرات',
                'الأسبوع الثالث | الذكاء الاصطناعي بالدفاع: كشف التهديدات وتحليل السجلات',
                'الأسبوع الرابع | الاستجابة للحوادث، المشروع النهائي، وتسليم الشهادات',
            ],
            'audience' => [
                'طلاب وخريجي الحاسوب والهندسة',
                'المبرمجين اللي يريدون يدخلون مجال الأمن',
                'موظفي تقنية المعلومات بالشركات والدوائر',
                'أي شخص يحب المجال ومستعد يتعلم بجدية',
            ],
            'includes' => [
                '12 جلسة حضورية (ساعتين لكل جلسة)',
                'تطبيق عملي بكل جلسة ومختبر خاص بيك',
                'مشروع نهائي تقدمه بآخر أسبوع',
                'شهادة إتمام قابلة للتحقق من غني أكاديمي',
                'مقعد مضمون ضمن 50 طالب فقط',
                'متابعة وأسئلة ويا المدرب طول فترة الكورس',
            ],
            'partners' => [
                'غني كود | الشريك التقني ومطور المنصة',
                'غني أكاديمي | التدريب والشهادات',
                'CPD Accreditation International | اعتماد مزود التدريب (CPD-10053)',
            ],
            'faq' => [
                'أحتاج خبرة سابقة؟ | لا. نبدأ من الأساسيات، بس لازم تعرف تستخدم الكمبيوتر بشكل جيد.',
                'شنو أجيب ويايه؟ | لابتوب (ذاكرة 8 كيكا أو أكثر) وشاحنته. نساعدك بتنصيب كل الأدوات بأول جلسة.',
                'شلون أدفع؟ | بالتحويل من محفظتك الإلكترونية. تحول المبلغ المكتوب بالضبط والنظام يأكد مقعدك تلقائيا.',
                'إذا ما كدرت أحضر جلسة؟ | تكدر تراجع محتوى الجلسة ويا المدرب، والمهم تحضر 10 جلسات على الأقل حتى تستلم الشهادة.',
                'الشهادة معتمدة؟ | تستلم شهادة إتمام من غني أكاديمي برقم تحقق، والأكاديمية مزود تدريب معتمد من CPD Accreditation International.',
                'أكدر أسترجع فلوسي؟ | تكدر تلغي وتسترجع المبلغ كامل قبل أول جلسة بـ 3 أيام أو أكثر.',
            ],
        ];
    }

    /** يقسم سطر «عنوان | نص» */
    public static function pair(string $line): array
    {
        $p = array_map('trim', explode('|', $line, 2));

        return [$p[0], $p[1] ?? ''];
    }
}
