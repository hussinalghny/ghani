@extends('layouts.admin')
@section('heading', 'استيراد من R2')
@section('sub', 'اختار مجلد الكورس، راجع الدروس، واضغط استيراد')

@section('content')
@if ($fill)
<div class="panel" id="dur" style="margin-bottom:16px" data-course="{{ $fill }}">
    <strong>نحسب مدة الدروس…</strong> <span class="muted" id="dur-t">لا تسكر الصفحة لحد ما يخلص.</span>
</div>
@endif
<div class="panel" style="margin-bottom:16px">
    <h2>شلون ترتب الملفات</h2>
    <p class="muted small" style="margin:0 0 8px">مجلد لكل كورس، وبداخله مجلد لكل فصل (اختياري)، والفيديوهات مرقمة حتى تنرتب صح:</p>
    <pre class="ltr" style="background:var(--bg-alt,#f6f5fb);padding:12px;border-radius:12px;font-size:13px;margin:0;overflow:auto">smart-defense/
  01 الأساسيات/
    01 - مقدمة الكورس.mp4
    02 - كيف يفكر المهاجم.mp4
  02 فحص الشبكات/
    01 - تنصيب الأدوات.mp4</pre>
    <p class="tiny muted" style="margin:8px 0 0">الرقم بأول الاسم يحدد الترتيب وما يظهر بالعنوان. تكدر تعدل أي عنوان قبل الاستيراد.</p>
</div>

<div class="panel" style="margin-bottom:16px">
    <h2>المجلدات</h2>
    <div class="row" style="gap:6px;flex-wrap:wrap">
        @forelse ($root['folders'] as $f)
            <a @class(['pill', 'active' => $prefix === $f]) href="{{ route('admin.storage.import', ['folder' => $f]) }}">{{ rtrim($f, '/') }}</a>
        @empty
            <span class="muted">ما أكو مجلدات بالـ bucket بعد.</span>
        @endforelse
    </div>
</div>

@if ($tree !== null)
<form class="panel" method="POST" action="{{ route('admin.storage.import.store') }}">
    @csrf
    <input type="hidden" name="folder" value="{{ $prefix }}">
    <div class="row-between" style="flex-wrap:wrap;gap:10px;align-items:end">
        <div class="field" style="min-width:260px"><label for="i-course">الكورس اللي تنضاف إله الدروس</label>
            <select class="input" id="i-course" name="course_id" required><option value="">اختار الكورس</option>@foreach ($courses as $c)<option value="{{ $c->id }}" @selected(old('course_id') == $c->id)>{{ $c->title }}</option>@endforeach</select></div>
        <button class="btn btn-primary" type="submit">استيراد الدروس المختارة</button>
    </div>
    @php($i = 0)
    @forelse ($tree as $sec)
        <h3 style="margin:22px 0 8px">{{ $sec['title'] }}</h3>
        <div class="table-wrap" style="border:0">
            <table class="table">
                <thead><tr><th style="width:40px"></th><th>العنوان</th><th>الفصل</th><th>الملف</th><th>الحجم</th><th>معاينة مجانية</th></tr></thead>
                <tbody>
                @foreach ($sec['files'] as $f)
                    <tr>
                        <td><input type="checkbox" name="items[{{ $i }}][on]" value="1" @checked(! $f['linked']) @disabled($f['linked']) aria-label="اختيار"></td>
                        <td>@if ($f['linked'])<span class="badge badge-green">مضاف: {{ $f['linked'] }}</span>@else<input class="input" name="items[{{ $i }}][title]" value="{{ $f['title'] }}" aria-label="عنوان الدرس">@endif</td>
                        <td>@unless ($f['linked'])<input class="input" name="items[{{ $i }}][section]" value="{{ $sec['title'] }}" aria-label="الفصل" style="max-width:200px">@endunless</td>
                        <td class="tiny ltr" style="max-width:260px;word-break:break-all">{{ $f['key'] }}<input type="hidden" name="items[{{ $i }}][key]" value="{{ $f['key'] }}"><input type="hidden" name="items[{{ $i }}][size]" value="{{ $f['size'] }}">@if ($f['linked'])<input type="hidden" name="items[{{ $i }}][title]" value="-"><input type="hidden" name="items[{{ $i }}][section]" value="-">@endif</td>
                        <td class="ltr tiny">{{ round($f['size'] / 1048576, 1) }} MB</td>
                        <td>@unless ($f['linked'])<input type="checkbox" name="items[{{ $i }}][free]" value="1" aria-label="معاينة مجانية">@endunless</td>
                    </tr>
                    @php($i++)
                @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="muted">ما أكو فيديوهات (mp4 / mov / webm) بهذا المجلد.</p>
    @endforelse
</form>
@endif
@endsection
@push('scripts')
<script>
(function () {
  var box = document.getElementById('dur'); if (!box) return;
  var t = document.getElementById('dur-t'), skip = [], total = 0, tries = 0;
  var token = document.querySelector('meta[name="csrf-token"]').content;
  function step() {
    var body = new FormData(); body.append('course_id', box.dataset.course);
    skip.forEach(function (id) { body.append('skip[]', id); });
    fetch('{{ route('admin.storage.durations') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: body })
      .then(function (r) { if (!r.ok) throw r; return r.json(); })
      .then(function (j) {
        tries = 0; total += j.done; skip = skip.concat(j.failed);
        if (j.left > 0) { t.textContent = 'خلصت ' + total + '، باقي ' + j.left + '…'; step(); }
        else { box.querySelector('strong').textContent = 'تمت.'; t.textContent = 'انحسبت مدة ' + total + ' درس' + (skip.length ? '، و' + skip.length + ' ما انقرت مدتها (تكدر تكتبها يدويا من المنهج).' : '.'); }
      })
      .catch(function () { if (++tries < 4) { setTimeout(step, 3000); } else { t.textContent = 'توقف الحساب. حدث الصفحة بعدين وراح يكمل من مكانه.'; } });
  }
  step();
})();
</script>
@endpush
