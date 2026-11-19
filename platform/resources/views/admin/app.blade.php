@extends('layouts.admin')
@section('heading', 'تطبيق الموبايل')
@section('sub', 'ارفع APK و IPA من هنا وتتحدث صفحة التنزيل فورا')
@section('actions')<a class="btn btn-plain btn-sm" href="{{ route('app') }}" target="_blank">@include('partials.icon', ['n' => 'eye', 'class' => 'icon icon-sm']) صفحة التنزيل</a>@endsection

@section('content')
<form method="POST" action="{{ route('admin.app.update') }}" enctype="multipart/form-data" style="max-width:860px" class="stack" id="appForm">
    @csrf
    <div class="kpis" style="grid-template-columns:repeat(3,minmax(0,1fr))">
        <div class="kpi"><span>الإصدار الحالي</span><strong class="ltr">{{ $release['version'] }}</strong><em style="color:var(--text-3)">{{ $release['updated'] ?? '—' }}</em></div>
        <div class="kpi"><span>تنزيلات Android</span><strong class="ltr">{{ number_format($release['android']['downloads']) }}</strong></div>
        <div class="kpi"><span>تنزيلات iPhone</span><strong class="ltr">{{ number_format($release['ios']['downloads']) }}</strong></div>
    </div>

    <div class="panel">
        <div class="form-grid">
            <div class="field"><label for="version">رقم الإصدار</label><input class="input" id="version" name="version" dir="ltr" value="{{ $release['version'] }}" required></div>
            <div class="field full"><label for="notes">الجديد بهذا الإصدار (اختياري)</label><textarea class="input" id="notes" name="notes" rows="3">{{ setting('app_notes') }}</textarea></div>
        </div>
    </div>

    <div class="grid grid-2">
        @foreach (['android' => ['Android', 'apk'], 'ios' => ['iPhone', 'ipa']] as $p => [$label, $ext])
            <div class="panel">
                <div class="row-between"><h2 style="margin:0">{{ $label }}</h2>
                    @if ($release[$p]['available'])<span class="badge badge-green">منشور@if ($release[$p]['size']) · <span class="ltr">{{ $release[$p]['size'] }} MB</span>@endif</span>@else<span class="badge badge-gray">غير متوفر</span>@endif
                </div>
                <div class="field" style="margin-top:14px"><label for="{{ $p }}">ملف .{{ $ext }}</label><input class="input" id="{{ $p }}" name="{{ $p }}" type="file" accept=".{{ $ext }}"></div>
                <div class="field"><label for="{{ $p }}_url">أو رابط خارجي (اختياري)</label><input class="input" id="{{ $p }}_url" name="{{ $p }}_url" type="url" dir="ltr" value="{{ setting("app_{$p}_url") }}" placeholder="https://..."></div>
                @if (setting("app_{$p}_file"))<label class="switch"><input type="checkbox" name="remove_{{ $p }}" value="1"> حذف الملف المرفوع</label>@endif
            </div>
        @endforeach
    </div>

    <div class="upbar" id="bar"><span></span></div>
    <p class="tiny" id="msg" style="margin:0"></p>
    <button class="btn btn-primary" type="submit" style="align-self:flex-start">نشر الإصدار</button>
    <p class="help">الملفات من GitHub Actions: نزلها من Artifacts وفك الضغط وارفع ملف .apk و .ipa هنا. إذا فشل رفع ملف كبير، زود <span class="ltr">upload_max_filesize</span> و <span class="ltr">post_max_size</span> إلى <span class="ltr">256M</span> من إعدادات PHP بـ Hostinger.</p>
</form>
@endsection

@push('scripts')
<script>
// رفع مع شريط تقدم لأن الملفات كبيرة
(function () {
  var f = document.getElementById('appForm'), bar = document.getElementById('bar'), msg = document.getElementById('msg');
  f.addEventListener('submit', function (e) {
    e.preventDefault();
    var x = new XMLHttpRequest();
    x.open('POST', f.action);
    x.upload.onprogress = function (ev) { if (ev.lengthComputable) { bar.style.display = 'block'; var p = Math.round(ev.loaded / ev.total * 100); bar.firstElementChild.style.width = p + '%'; msg.textContent = 'جاري الرفع ' + p + '%'; } };
    x.onload = function () { if (x.status < 400) { location.reload(); } else { msg.textContent = x.status === 413 ? 'الملف أكبر من الحد المسموح بالسيرفر.' : 'فشل الرفع (' + x.status + ').'; } };
    x.onerror = function () { msg.textContent = 'انقطع الاتصال أثناء الرفع.'; };
    f.querySelector('[type=submit]').disabled = true;
    x.send(new FormData(f));
  });
})();
</script>
@endpush
