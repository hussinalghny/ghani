@extends('layouts.app')
@section('title', 'تنزيل التطبيق')
@section('description', 'نزل تطبيق غني أكاديمي على Android و iPhone وتعلم من أي مكان.')

@section('content')
<section class="hero" style="padding-bottom:24px">
    <div class="container">
        <span class="eyebrow">الإصدار <span class="ltr">{{ $release['version'] }}</span></span>
        <h1>غني أكاديمي بجيبك<span class="dot">.</span></h1>
        <p class="lead">كورساتك، تقدمك، ومحفظتك بتطبيق واحد. نفس حسابك بالمنصة.</p>
    </div>
</section>

<section class="section" style="padding-top:8px">
    <div class="container" style="max-width:900px">
        <div class="grid grid-2">
            @foreach (['android' => ['Android', 'phone', 'ملف APK'], 'ios' => ['iPhone', 'phone', 'ملف IPA']] as $p => [$label, $icon, $kind])
                @php($r = $release[$p])
                <div class="panel app-card" data-platform="{{ $p }}" style="padding:28px;text-align:center">
                    <span class="cat-icon" style="margin:0 auto 14px;width:60px;height:60px;border-radius:18px">@include('partials.icon', ['n' => $icon])</span>
                    <h2 style="font-size:24px;margin-bottom:4px">{{ $label }}<span class="dot">.</span></h2>
                    <p class="small muted" style="margin-bottom:18px">{{ $kind }}@if ($r['size']) · <span class="ltr">{{ $r['size'] }} MB</span>@endif</p>
                    @if ($r['available'])
                        <a class="btn btn-primary btn-block" href="{{ route('app.download', $p) }}">@include('partials.icon', ['n' => 'download']) تنزيل لـ {{ $label }}</a>
                        <img class="qr hide-sm" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=6&color=1B1446&data={{ urlencode(route('app.download', $p)) }}" alt="امسح الرمز بكاميرا الموبايل" width="150" height="150" style="margin:20px auto 6px;border-radius:12px;border:1px solid var(--line)">
                        <p class="tiny hide-sm">امسح الرمز بكاميرا الموبايل</p>
                    @else
                        <button class="btn btn-plain btn-block" disabled>قريبا</button>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($release['notes'])
            <div class="panel" style="margin-top:20px">
                <h2>الجديد بهذا الإصدار</h2>
                <div class="prose">{{ $release['notes'] }}</div>
            </div>
        @endif

        <div class="grid grid-2" style="margin-top:20px">
            <div class="panel">
                <h2>التثبيت على Android</h2>
                <ol class="small muted" style="padding-inline-start:20px;margin:0;line-height:2">
                    <li>اضغط تنزيل وافتح الملف بعد ما يكمل.</li>
                    <li>إذا طلب، فعّل «السماح بالتثبيت من هذا المصدر».</li>
                    <li>اضغط تثبيت، وسجل دخولك بنفس حساب المنصة.</li>
                </ol>
            </div>
            <div class="panel">
                <h2>التثبيت على iPhone</h2>
                <ol class="small muted" style="padding-inline-start:20px;margin:0;line-height:2">
                    <li>نزل ملف IPA على الآيفون.</li>
                    <li>ثبته عبر تطبيق ESign أو Sideloadly.</li>
                    <li>من الإعدادات ← عام ← إدارة الجهاز، اضغط «وثوق».</li>
                </ol>
            </div>
        </div>
        <p class="tiny" style="text-align:center;margin-top:20px">التطبيق يمنع تصوير الشاشة لحماية المحتوى. لأي مشكلة راسلنا على واتساب <a class="ltr" href="https://wa.me/964{{ ltrim(config('academy.whatsapp'), '0') }}">0776 663 7444</a>.</p>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
  // نبرز نسخة جهاز الزائر
  var ua = navigator.userAgent, p = /iPhone|iPad|iPod/.test(ua) ? 'ios' : (/Android/.test(ua) ? 'android' : null);
  if (!p) return;
  var card = document.querySelector('.app-card[data-platform="' + p + '"]');
  if (card) { card.style.borderColor = 'var(--violet)'; card.style.boxShadow = '0 0 0 4px rgba(90,53,214,.10)'; card.parentElement.prepend(card); }
})();
</script>
@endpush
