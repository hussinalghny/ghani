@extends('layouts.admin')
@section('heading', 'تخزين الفيديو')
@section('sub', 'Cloudflare R2: الفيديوهات تتحمل من كلاودفلير بدل هوستنجر، والحماية تبقى نفسها')

@section('content')
<div class="kpis">
    <div class="kpi"><span>الحالة</span><strong>{{ $conf['enabled'] ? 'R2 مفعل' : 'على السيرفر' }}</strong></div>
    <div class="kpi"><span>فيديوهات على R2</span><strong class="ltr">{{ $onR2 }}</strong></div>
    <div class="kpi"><span>باقية على السيرفر</span><strong class="ltr">{{ $local->count() }}</strong><em><span class="ltr">{{ number_format($local->sum('size_mb') / 1024, 2) }}</span> كيكا</em></div>
</div>

<form class="panel stack" method="POST" action="{{ route('admin.storage.update') }}" style="margin-bottom:16px">
    @csrf @method('PUT')
    <h2>إعدادات R2</h2>
    <p class="muted small" style="margin:0">من لوحة Cloudflare: R2 ← Manage R2 API Tokens ← Create API Token بصلاحية <b class="ltr">Object Read &amp; Write</b> على الـ bucket، وانسخ المعلومات هنا. الـ bucket خليه خاص (بدون رابط عام).</p>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px">
        <div class="field"><label for="r-acc">Account ID</label><input class="input ltr" id="r-acc" name="r2_account_id" dir="ltr" required value="{{ old('r2_account_id', $conf['account']) }}" autocomplete="off"></div>
        <div class="field"><label for="r-bucket">اسم الـ Bucket</label><input class="input ltr" id="r-bucket" name="r2_bucket" dir="ltr" required value="{{ old('r2_bucket', $conf['bucket']) }}" placeholder="ghani-videos" autocomplete="off"></div>
        <div class="field"><label for="r-key">Access Key ID</label><input class="input ltr" id="r-key" name="r2_access_key" dir="ltr" required value="{{ old('r2_access_key', $conf['key']) }}" autocomplete="off"></div>
        <div class="field"><label for="r-sec">Secret Access Key</label><input class="input ltr" id="r-sec" name="r2_secret" type="password" dir="ltr" autocomplete="new-password" placeholder="{{ $conf['has_secret'] ? 'محفوظ (اتركه فارغ حتى ما يتغير)' : '' }}"></div>
    </div>
    <label class="row" style="gap:8px"><input type="checkbox" name="r2_enabled" value="1" @checked($conf['enabled'])> تفعيل R2: الفيديوهات الجديدة تنرفع على R2 تلقائيا</label>
    <div class="row" style="gap:8px">
        <button class="btn btn-primary" type="submit">حفظ</button>
        <button class="btn btn-plain" type="submit" name="test" value="1">حفظ واختبار الاتصال</button>
    </div>
    <p class="tiny muted" style="margin:0">المفتاح السري ينحفظ مشفر بقاعدة البيانات وما ينعرض مرة ثانية.</p>
</form>

<div class="panel">
    <div class="row-between" style="flex-wrap:wrap;gap:10px">
        <h2 style="margin:0">نقل الفيديوهات الحالية لـ R2</h2>
        @if ($local->isNotEmpty())<button class="btn btn-primary" type="button" id="moveAll">نقل الكل (<span class="ltr">{{ $local->count() }}</span>)</button>@endif
    </div>
    <p class="muted small">كل فيديو ينرفع لـ R2، وبعد ما ينجح ينحذف من هوستنجر. خلي الصفحة مفتوحة لحد ما يخلص. إذا انقطع، اضغط مرة ثانية ويكمل من وين ما وكف.</p>
    <div class="table-wrap" style="border:0">
        <table class="table">
            <thead><tr><th>الكورس</th><th>الدرس</th><th>الحجم</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @forelse ($local as $l)
                <tr data-move="{{ route('admin.storage.move', $l) }}">
                    <td>{{ $l->course?->title }}</td>
                    <td>{{ $l->title }}</td>
                    <td class="ltr">{{ $l->size_mb !== null ? $l->size_mb.' MB' : '—' }}</td>
                    <td class="st">{{ $l->size_mb === null ? 'الملف مو موجود' : 'على السيرفر' }}</td>
                    <td><button class="btn btn-plain btn-sm one" type="button" @disabled($l->size_mb === null)>نقل</button></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">كل الفيديوهات على R2.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var token = document.querySelector('meta[name="csrf-token"]').content;
  function move(tr) {
    var st = tr.querySelector('.st'), btn = tr.querySelector('.one');
    if (btn.disabled || tr.dataset.done) return Promise.resolve(true);
    st.textContent = 'دينرفع...'; btn.disabled = true;
    return fetch(tr.dataset.move, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } })
      .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'HTTP ' + r.status }; }); })
      .then(function (j) {
        if (j.ok) { st.textContent = 'انتقل ✓'; tr.dataset.done = 1; return true; }
        st.textContent = 'فشل: ' + (j.message || ''); btn.disabled = false; return false;
      })
      .catch(function () { st.textContent = 'انقطع الاتصال'; btn.disabled = false; return false; });
  }
  document.querySelectorAll('tr[data-move] .one').forEach(function (b) { b.addEventListener('click', function () { move(b.closest('tr')); }); });
  var all = document.getElementById('moveAll');
  if (all) all.addEventListener('click', async function () {
    all.disabled = true;
    for (const tr of document.querySelectorAll('tr[data-move]')) { await move(tr); }
    all.disabled = false; all.textContent = 'خلص النقل';
  });
})();
</script>
@endpush
