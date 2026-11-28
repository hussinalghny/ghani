@extends('layouts.admin')
@section('heading', 'أكواد الخصم')
@section('sub', 'للكورسات الإلكترونية والحضوري: نسبة أو مبلغ، بعدد استخدامات وتاريخ انتهاء')

@section('content')
<div class="panel" style="margin-bottom:16px">
    <h2>كود جديد</h2>
    <form method="POST" action="{{ route('admin.discounts.store') }}" class="grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end">
        @csrf
        <div class="field"><label for="d-code">الكود</label><input class="input ltr" id="d-code" name="code" dir="ltr" required placeholder="GHANI20" value="{{ old('code') }}" style="text-transform:uppercase"></div>
        <div class="field"><label for="d-type">النوع</label><select class="input" id="d-type" name="type"><option value="percent" @selected(old('type') === 'percent')>نسبة %</option><option value="fixed" @selected(old('type') === 'fixed')>مبلغ ثابت (د.ع)</option></select></div>
        <div class="field"><label for="d-val">قيمة الخصم</label><input class="input ltr" id="d-val" name="value" type="number" dir="ltr" required min="1" value="{{ old('value') }}"></div>
        <div class="field" style="grid-column:span 2"><label for="d-target">ينطبق على</label>
            <select class="input" id="d-target" name="target">
                <option value="course" @selected(old('target', 'course') === 'course')>كل الكورسات الإلكترونية</option>
                <option value="all" @selected(old('target') === 'all')>كل الكورسات الإلكترونية والحضورية</option>
                <option value="cohort" @selected(old('target') === 'cohort')>كل الكورسات الحضورية</option>
                <optgroup label="كورس إلكتروني محدد">
                    @foreach ($courses as $c)<option value="course:{{ $c->id }}" @selected(old('target') === 'course:'.$c->id)>{{ $c->title }}</option>@endforeach
                </optgroup>
                <optgroup label="كورس حضوري محدد">
                    @foreach ($cohorts as $c)<option value="cohort:{{ $c->id }}" @selected(old('target') === 'cohort:'.$c->id)>{{ $c->title }}</option>@endforeach
                </optgroup>
            </select>
        </div>
        <div class="field"><label for="d-max">عدد الاستخدامات</label><input class="input ltr" id="d-max" name="max_uses" type="number" dir="ltr" min="1" placeholder="بلا حد" value="{{ old('max_uses') }}"></div>
        <div class="field"><label for="d-exp">ينتهي</label><input class="input ltr" id="d-exp" name="expires_at" type="date" dir="ltr" value="{{ old('expires_at') }}"></div>
        <div class="field"><label for="d-note">ملاحظة</label><input class="input" id="d-note" name="note" placeholder="مثلا: حملة إنستغرام" value="{{ old('note') }}"></div>
        <button class="btn btn-primary" type="submit">إضافة كود</button>
    </form>
</div>

<div class="panel">
    <h2>الأكواد</h2>
    <div class="table-wrap" style="border:0">
        <table class="table">
            <thead><tr><th>الكود</th><th>الخصم</th><th>ينطبق على</th><th>الاستخدام</th><th>المبيعات</th><th>ينتهي</th><th>رابط جاهز</th><th></th></tr></thead>
            <tbody>
            @forelse ($codes as $d)
                @php($link = $d->course ? route('courses.show', $d->course).'?code='.$d->code.'#buy' : ($d->cohort ? route('cohort.show', $d->cohort).'?code='.$d->code.'#register' : null))
                <tr @class(['muted' => ! $d->is_active])>
                    <td class="ltr"><strong>{{ $d->code }}</strong>@if ($d->note)<div class="tiny muted" dir="rtl">{{ $d->note }}</div>@endif</td>
                    <td>{{ $d->label() }}</td>
                    <td class="tiny">{{ $d->target() }}</td>
                    <td class="ltr">{{ $d->uses() }}{{ $d->max_uses ? ' / '.$d->max_uses : '' }}</td>
                    <td class="ltr">{{ number_format($d->revenue()) }}</td>
                    <td class="tiny ltr">{{ $d->expires_at?->format('Y/m/d') ?? '—' }}</td>
                    <td>@if ($link)<button class="btn btn-plain btn-sm copy-btn" type="button" data-copy="{{ $link }}">نسخ الرابط</button>@else<span class="tiny muted">يكتبه الطالب</span>@endif</td>
                    <td><form method="POST" action="{{ route('admin.discounts.toggle', $d) }}">@csrf<button class="btn btn-sm {{ $d->is_active ? 'btn-plain' : 'btn-primary' }}" type="submit">{{ $d->is_active ? 'إيقاف' : 'تفعيل' }}</button></form></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">ما أكو أكواد بعد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="tiny muted" style="margin-top:10px">الطالب يكتب الكود بصفحة الكورس قبل الدفع، أو يفتح الرابط الجاهز والكود ينطبق لحاله. الاستخدام يحسب المدفوع والمحجوز بمهلة الدفع.</p>
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach(function (b) {
  b.addEventListener('click', function () {
    var v = b.dataset.copy, t0 = b.textContent;
    function done() { b.textContent = 'انسخ'; setTimeout(function () { b.textContent = t0; }, 1400); }
    if (navigator.clipboard) { navigator.clipboard.writeText(v).then(done, done); } else { var t = document.createElement('textarea'); t.value = v; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (e) {} t.remove(); done(); }
  });
});
</script>
@endpush
