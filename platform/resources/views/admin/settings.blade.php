@extends('layouts.admin')
@section('heading', 'الحماية والإعدادات')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" style="max-width:820px" class="stack">
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

    <button class="btn btn-primary" type="submit" style="align-self:flex-start">حفظ الإعدادات</button>
</form>
@endsection
