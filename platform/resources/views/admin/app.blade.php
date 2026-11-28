@extends('layouts.admin')
@section('heading', 'تطبيق الموبايل')
@section('sub', 'ارفع APK و IPA من هنا وتتحدث صفحة التنزيل فورا')
@section('actions')<a class="btn btn-plain btn-sm" href="{{ route('app') }}" target="_blank">@include('partials.icon', ['n' => 'eye', 'class' => 'icon icon-sm']) صفحة التنزيل</a>@endsection

@section('content')
@php($b = $banner ?? [])
@php($act = setting('banner_action') ?: 'courses')
<form method="POST" action="{{ route('admin.app.banner') }}" enctype="multipart/form-data" class="panel" style="max-width:860px;margin-bottom:16px" id="bannerForm">
    @csrf
    <div class="row-between"><h2 style="margin:0">بنر الرئيسية بالتطبيق</h2>
        <label class="row" style="gap:8px"><input type="checkbox" name="banner_on" value="1" @checked($banner !== null)> ظاهر</label></div>
    <p class="tiny muted" style="margin:4px 0 14px">يتغير فورا بالتطبيق من نسخة 27 وطالع، بدون تحديث جديد. يظهر بالرئيسية تحت «كمل التعلم».</p>
    <div class="grid" style="grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start">
        <div class="stack" style="gap:10px">
            <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px">
                <div class="field" style="margin:0"><label for="b-badge">الشارة</label><input class="input" id="b-badge" name="banner_badge" maxlength="40" value="{{ old('banner_badge', $b['badge'] ?? 'غني أكاديمي') }}" placeholder="فارغ = بدون شارة"></div>
                <div class="field" style="margin:0"><label for="b-color">لون الخلفية</label><input class="input" id="b-color" name="banner_color" type="color" value="{{ old('banner_color', $b['color'] ?? '#5A35D6') }}" style="padding:4px;height:48px"></div>
            </div>
            <div class="field" style="margin:0"><label for="b-title">العنوان</label><input class="input" id="b-title" name="banner_title" maxlength="80" required value="{{ old('banner_title', $b['title'] ?? '') }}"></div>
            <div class="field" style="margin:0"><label for="b-text">النص</label><input class="input" id="b-text" name="banner_text" maxlength="140" value="{{ old('banner_text', $b['text'] ?? '') }}"></div>
            <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px">
                <div class="field" style="margin:0"><label for="b-btn">نص الزر</label><input class="input" id="b-btn" name="banner_button" maxlength="30" value="{{ old('banner_button', $b['button'] ?? 'تصفح الكورسات') }}" placeholder="فارغ = بدون زر"></div>
                <div class="field" style="margin:0"><label for="b-act">لما يضغط يروح إلى</label>
                    <select class="input" id="b-act" name="banner_action">
                        <option value="courses" @selected($act === 'courses')>قائمة الكورسات</option>
                        <optgroup label="كورس">@foreach ($courses as $c)<option value="course:{{ $c->slug }}" @selected($act === 'course:'.$c->slug)>{{ $c->title }}</option>@endforeach</optgroup>
                        <optgroup label="كورس حضوري">@foreach ($cohorts as $c)<option value="cohort:{{ $c->slug }}" @selected($act === 'cohort:'.$c->slug)>{{ $c->title }}</option>@endforeach</optgroup>
                        <option value="url" @selected($act === 'url')>رابط خارجي</option>
                    </select>
                </div>
            </div>
            <div class="field" style="margin:0" id="b-url-f"><label for="b-url">الرابط</label><input class="input ltr" id="b-url" name="banner_url" dir="ltr" placeholder="https://" value="{{ old('banner_url', setting('banner_url')) }}"></div>
            <div class="field" style="margin:0"><label for="b-img">صورة خلفية (اختياري)</label><input type="file" id="b-img" name="banner_image" accept="image/*"><small class="tiny muted">عرضية، مثلا 1200×600. النص ينكتب فوقها ويا تظليل حتى يبقى مقروء.</small>
                @if (! empty($b['image']))<label class="row tiny" style="gap:6px;margin-top:6px"><input type="checkbox" name="banner_image_clear" value="1"> حذف الصورة الحالية</label>@endif
            </div>
            <button class="btn btn-primary" type="submit" style="align-self:flex-start">حفظ البنر</button>
        </div>
        <div>
            <div class="tiny muted" style="margin-bottom:6px">معاينة</div>
            <div id="bp" style="position:relative;border-radius:18px;overflow:hidden;padding:20px;color:#fff;min-height:150px;background:{{ $b['color'] ?? '#5A35D6' }} center/cover no-repeat">
                <div id="bp-img" style="position:absolute;inset:0;background:center/cover no-repeat;{{ ! empty($b['image']) ? 'background-image:linear-gradient(90deg,rgba(18,12,51,.15),rgba(18,12,51,.78)),url('.$b['image'].')' : '' }}"></div>
                <div style="position:relative">
                    <span id="bp-badge" style="display:inline-block;background:#F5B83D;color:#1B1446;font-weight:700;font-size:12px;padding:1px 8px;border-radius:6px"></span>
                    <div id="bp-title" style="font-family:var(--font-head);font-weight:700;font-size:19px;line-height:1.4;margin-top:10px"></div>
                    <div id="bp-text" style="color:#DDD4FF;font-size:13px;margin-top:4px"></div>
                    <span id="bp-btn" style="display:inline-block;background:#fff;color:#1B1446;font-weight:700;padding:7px 16px;border-radius:10px;margin-top:14px;font-size:14px"></span>
                </div>
            </div>
        </div>
    </div>
</form>
<form method="POST" action="{{ route('admin.app.update') }}" enctype="multipart/form-data" style="max-width:860px" class="stack" id="appForm">
    @csrf
    <div class="kpis" style="grid-template-columns:repeat(3,minmax(0,1fr))">
        <div class="kpi"><span>الإصدار الحالي</span><strong class="ltr">{{ $release['version'] }}</strong><em style="color:var(--text-3)">{{ $release['updated'] ?? '—' }}</em></div>
        <div class="kpi"><span>تنزيلات Android</span><strong class="ltr">{{ number_format($release['android']['downloads']) }}</strong></div>
        <div class="kpi"><span>تنزيلات iPhone</span><strong class="ltr">{{ number_format($release['ios']['downloads']) }}</strong></div>
        <div class="kpi"><span>تنزيلات Windows</span><strong class="ltr">{{ number_format($release['windows']['downloads']) }}</strong></div>
    </div>

    <div class="panel">
        <div class="form-grid">
            <div class="field"><label for="version">رقم الإصدار</label><input class="input" id="version" name="version" dir="ltr" value="{{ $release['version'] }}" required></div>
            <div class="field full"><label for="notes">الجديد بهذا الإصدار (اختياري)</label><textarea class="input" id="notes" name="notes" rows="3">{{ setting('app_notes') }}</textarea></div>
        </div>
    </div>

    <div class="grid grid-2">
        @foreach (['android' => ['Android', 'apk'], 'ios' => ['iPhone', 'ipa'], 'windows' => ['Windows (كمبيوتر)', 'exe']] as $p => [$label, $ext])
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

@push('scripts')
<script>
(function () {
  var f = document.getElementById('bannerForm'); if (!f) return;
  var $ = function (id) { return document.getElementById(id); };
  function show(el, v) { el.textContent = v; el.style.display = v ? '' : 'none'; }
  function sync() {
    show($('bp-badge'), $('b-badge').value); show($('bp-title'), $('b-title').value);
    show($('bp-text'), $('b-text').value); show($('bp-btn'), $('b-btn').value);
    $('bp').style.backgroundColor = $('b-color').value;
    $('b-url-f').style.display = $('b-act').value === 'url' ? '' : 'none';
  }
  f.addEventListener('input', sync); f.addEventListener('change', sync);
  $('b-img').addEventListener('change', function () {
    var file = this.files[0]; if (!file) return;
    $('bp-img').style.backgroundImage = 'linear-gradient(90deg,rgba(18,12,51,.15),rgba(18,12,51,.78)),url(' + URL.createObjectURL(file) + ')';
  });
  sync();
})();
</script>
@endpush
