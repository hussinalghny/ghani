@extends('layouts.admin')
@section('heading', 'إصدار دفعة قسائم')

@section('content')
<form class="panel" style="max-width:760px" method="POST" action="{{ route('admin.vouchers.store') }}" data-once id="vf">
    @csrf
    <div class="field">
        <label>نوع القسيمة</label>
        <div class="pills">
            <label class="pill" style="cursor:pointer"><input type="radio" name="kind" value="balance" @checked(old('kind', 'balance') === 'balance')> قسيمة رصيد</label>
            <label class="pill" style="cursor:pointer"><input type="radio" name="kind" value="course" @checked(old('kind') === 'course')> قسيمة كورس محدد</label>
        </div>
        <span class="help">قسيمة الرصيد تنضاف للمحفظة. قسيمة الكورس تفتح الكورس مباشرة بدون رصيد.</span>
    </div>
    <div class="form-grid">
        <div class="field" data-kind="balance"><label for="amount">القيمة (د.ع)</label>
            <input class="input" id="amount" name="amount" type="number" min="250" step="250" dir="ltr" list="denoms" value="{{ old('amount', $denominations[1] ?? 10000) }}">
            <datalist id="denoms">@foreach ($denominations as $d)<option value="{{ $d }}">@endforeach</datalist>
        </div>
        <div class="field" data-kind="course"><label for="course_id">الكورس</label>
            <select class="input" id="course_id" name="course_id"><option value="">اختر</option>@foreach ($courses as $c)<option value="{{ $c->id }}" @selected(old('course_id') == $c->id)>{{ $c->title }} · {{ iqd($c->finalPrice()) }}</option>@endforeach</select>
        </div>
        <div class="field"><label for="quantity">العدد</label><input class="input" id="quantity" name="quantity" type="number" min="1" max="2000" dir="ltr" value="{{ old('quantity', 50) }}" required></div>
        <div class="field"><label for="name">اسم الدفعة (اختياري)</label><input class="input" id="name" name="name" value="{{ old('name') }}" placeholder="مكتبة النهرين - تشرين الثاني"></div>
        <div class="field"><label for="expires_at">تنتهي في (اختياري)</label><input class="input" id="expires_at" name="expires_at" type="date" dir="ltr" value="{{ old('expires_at') }}"></div>
        <div class="field full"><label for="reseller_id">تخصيص لموزع (اختياري)</label>
            <select class="input" id="reseller_id" name="reseller_id"><option value="">بدون — بيع مباشر من الأكاديمية</option>@foreach ($resellers as $r)<option value="{{ $r->id }}" @selected(old('reseller_id') == $r->id)>{{ $r->name }} · {{ $r->phone }} · خصم {{ $r->reseller_discount }}% · رصيد {{ iqd($r->balance) }}</option>@endforeach</select>
            <span class="help">القسائم تظهر ببوابة الموزع ويقدر يطبعها ويتابع مبيعاتها.</span>
        </div>
        <div class="field full"><label class="switch"><input type="checkbox" name="charge_reseller" value="1" @checked(old('charge_reseller'))> اخصم الكلفة من رصيد الموزع (حسب نسبة خصمه)</label></div>
    </div>
    <button class="btn btn-primary" type="submit">إصدار القسائم</button>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var f = document.getElementById('vf');
  var sync = function () {
    var k = f.querySelector('[name=kind]:checked').value;
    f.querySelectorAll('[data-kind]').forEach(function (el) { el.style.display = el.dataset.kind === k ? '' : 'none'; });
  };
  f.addEventListener('change', sync); sync();
})();
</script>
@endpush
