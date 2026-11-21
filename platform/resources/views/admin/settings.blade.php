@extends('layouts.admin')
@section('heading', 'الحماية والإعدادات')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" style="max-width:820px" class="stack" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="panel">
        <h2>الحماية من التسريب ومشاركة الحسابات</h2>
        <div class="form-grid">
            <div class="field"><label for="max_devices">عدد الأجهزة لكل طالب</label><input class="input" id="max_devices" name="max_devices" type="number" min="0" max="10" dir="ltr" value="{{ setting('max_devices') }}"><span class="help">مثلا 2: موبايل + لابتوب. 0 = بلا حد.</span></div>
            <div class="field"><label for="lesson_opens_per_hour">حد فتح الدروس بالساعة</label><input class="input" id="lesson_opens_per_hour" name="lesson_opens_per_hour" type="number" min="0" dir="ltr" value="{{ setting('lesson_opens_per_hour') }}"><span class="help">يوقف أدوات سحب الفيديوهات. 0 = بلا حد.</span></div>
        </div>
        <div class="stack">
            <label class="switch"><input type="checkbox" name="single_session" value="1" @checked(setting('single_session'))> جلسة واحدة: الدخول من متصفح أو تطبيق جديد يطلع القديم</label>
            <label class="switch"><input type="checkbox" name="watermark" value="1" @checked(setting('watermark'))> علامة مائية متحركة باسم ورقم الطالب على الفيديو</label>
        </div>
        <h2 style="margin-top:22px">أماكن تشغيل الفيديو</h2>
        @php($ch = \App\Services\LessonAccess::CHANNEL_DEFAULTS)
        <div class="stack">
            <label class="switch"><input type="checkbox" name="video_desktop" value="1" @checked(setting('video_desktop', $ch['desktop']))> تطبيق الكمبيوتر (الأقوى: النافذة سودة بأي تسجيل)</label>
            <label class="switch"><input type="checkbox" name="video_app" value="1" @checked(setting('video_app', $ch['app']))> تطبيق الموبايل (منع السكرين شوت والتسجيل)</label>
            <label class="switch"><input type="checkbox" name="video_web" value="1" @checked(setting('video_web', $ch['web']))> الموقع بالمتصفح (أضعف حماية)</label>
            <span class="help">المكان المقفول يطلع للطالب رسالة وزر تنزيل التطبيق.</span>
        </div>
        <div class="alert" style="margin-top:18px">
            @include('partials.icon', ['n' => 'shield'])
            <span>Bunny Stream: {{ $bunny ? 'مربوط' : 'غير مربوط' }}. فعّل من لوحة Bunny: Embed Token Authentication، و Allowed Domains لدومين المنصة فقط، و MediaCage DRM إذا متاح بخطتك.</span>
        </div>
    </div>

    <div class="panel">
        <h2>القسائم والموزعين</h2>
        <div class="form-grid">
            <div class="field"><label for="voucher_denominations">فئات القسائم للموزعين</label><input class="input" id="voucher_denominations" name="voucher_denominations" dir="ltr" value="{{ setting('voucher_denominations') }}"><span class="help">أرقام مفصولة بفاصلة.</span></div>
            <div class="field"><label for="reseller_discount">خصم الموزع الافتراضي %</label><input class="input" id="reseller_discount" name="reseller_discount" type="number" min="0" max="60" dir="ltr" value="{{ setting('reseller_discount') }}"></div>
        </div>
    </div>

    <div class="panel">
        <h2>شريط إعلان أعلى المنصة</h2>
        <input class="input" name="announcement" value="{{ setting('announcement') }}" placeholder="مثال: خصم 20% على كورس الدفاع الذكي حتى نهاية الشهر" aria-label="الإعلان">
    </div>

    <div class="panel">
        <h2>إنترو الفيديو</h2>
        @php($intro = \App\Services\LessonAccess::intro() ?? ['url' => setting('intro_file') ? asset('storage/'.setting('intro_file')) : asset('media/ghani-intro.mp4')])
        <label class="switch"><input type="checkbox" name="intro_enabled" value="1" @checked((string) setting('intro_enabled', '1') === '1')> تشغيل الإنترو تلقائيا قبل الدروس</label>
        <div class="form-grid" style="margin-top:12px">
            <div class="field"><label for="intro_mode">متى يطلع</label>
                <select class="input" id="intro_mode" name="intro_mode">
                    <option value="every" @selected((setting('intro_mode') ?: 'every') === 'every')>قبل كل درس</option>
                    <option value="session" @selected(setting('intro_mode') === 'session')>أول درس بس كل ما يفتح الطالب التطبيق أو الموقع</option>
                </select></div>
            <div class="field"><label for="intro_skip_after">زر التخطي</label>
                <select class="input" id="intro_skip_after" name="intro_skip_after">
                    @foreach ([0 => 'فورا', 2 => 'بعد ثانيتين', 3 => 'بعد 3 ثواني', 5 => 'بعد 5 ثواني', -1 => 'بدون تخطي'] as $k => $v)<option value="{{ $k }}" @selected((int) (setting('intro_skip_after') ?? 3) === $k)>{{ $v }}</option>@endforeach
                </select></div>
            <div class="field full"><label for="intro_file">فيديو إنترو بديل (MP4، أقل من 50MB)</label>
                <video src="{{ $intro['url'] }}" controls muted playsinline style="width:100%;max-width:420px;border-radius:14px;background:#000;margin-bottom:10px"></video>
                <input class="input" type="file" id="intro_file" name="intro_file" accept="video/mp4">
                @if (setting('intro_file'))<label class="switch" style="margin-top:8px"><input type="checkbox" name="intro_reset" value="1"> الرجوع لإنترو الأكاديمية الافتراضي</label>@endif
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>واجهة الرئيسية</h2>
        <div class="form-grid">
            <div class="field full"><label for="home_badge">النص فوق العنوان</label><input class="input" id="home_badge" name="home_badge" value="{{ setting('home_badge') }}" placeholder="التسجيل مفتوح · كورسات تقنية عملية"></div>
            <div class="field full"><label for="home_hero_image">الصورة الكبيرة بالرئيسية</label>
                @if (setting('home_hero_image'))<img src="{{ asset('storage/'.setting('home_hero_image')) }}" alt="" style="width:180px;border-radius:14px;margin-bottom:10px;aspect-ratio:4/5;object-fit:cover">@endif
                <input class="input" type="file" id="home_hero_image" name="home_hero_image" accept="image/*"><span class="help">مقاس مقترح 1200×1500 (طولية). بدونها تطلع صورة أول كورس مميز.</span></div>
        </div>
    </div>

    <button class="btn btn-primary" type="submit" style="align-self:flex-start">حفظ الإعدادات</button>
</form>
@endsection
