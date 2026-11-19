@extends('layouts.app')
@section('title', 'بوابة الموزعين')

@section('content')
<div class="container">
    <div class="page-head">
        <h1>بوابة الموزعين<span class="dot">.</span></h1>
        <p>اصدر قسائم رصيد بخصم <span class="ltr">{{ $user->reseller_discount }}%</span> وبيعها للطلاب.</p>
    </div>

    <div class="grid grid-2" style="align-items:start">
        <div class="stack">
            <div class="wallet-card">
                <div class="row-between"><span class="lbl">رصيد الموزع</span><img src="{{ asset('img/logo-dark.png') }}" alt=""></div>
                <div class="amt"><span class="ltr">{{ iqd($user->balance, false) }}</span> <small>د.ع</small></div>
                <span class="lbl">لزيادة رصيدك راسل الإدارة على الواتساب.</span>
            </div>
            <div class="grid grid-3" style="gap:12px">
                <div class="panel" style="padding:16px"><span class="tiny">متاحة للبيع</span><div class="price ltr">{{ $stats['available'] }}</div></div>
                <div class="panel" style="padding:16px"><span class="tiny">مباعة</span><div class="price ltr">{{ $stats['sold'] }}</div></div>
                <div class="panel" style="padding:16px"><span class="tiny">مستخدمة</span><div class="price ltr">{{ $stats['redeemed'] }}</div></div>
            </div>
        </div>

        <div class="panel">
            <h2>إصدار قسائم جديدة</h2>
            <form method="POST" action="{{ route('reseller.issue') }}" data-once id="issueForm" data-discount="{{ $user->reseller_discount }}">
                @csrf
                <div class="field">
                    <label>الفئة</label>
                    <div class="pills" style="flex-wrap:wrap">
                        @foreach ($denominations as $i => $d)
                            <label class="pill" style="cursor:pointer"><input type="radio" name="amount" value="{{ $d }}" @checked($i === 0) style="accent-color:var(--violet)"> <span class="ltr">{{ iqd($d, false) }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label for="quantity">العدد</label>
                    <input class="input" id="quantity" name="quantity" type="number" min="1" max="500" value="10" dir="ltr" required>
                </div>
                <div class="alert" style="margin-bottom:16px">الكلفة عليك: <strong class="ltr" id="cost">—</strong> د.ع</div>
                <button class="btn btn-primary btn-block" type="submit">إصدار القسائم</button>
            </form>
        </div>
    </div>

    <h2 style="font-size:24px;margin-top:40px">الدفعات<span class="dot">.</span></h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>الدفعة</th><th>الفئة</th><th>العدد</th><th>مباعة</th><th>مستخدمة</th><th>الكلفة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            @forelse ($batches as $b)
                <tr>
                    <td><a href="{{ route('reseller.batch', $b) }}">{{ $b->name }}</a></td>
                    <td class="ltr">{{ iqd($b->amount, false) }}</td>
                    <td class="ltr">{{ $b->vouchers_count }}</td>
                    <td class="ltr">{{ $b->sold_count }}</td>
                    <td class="ltr">{{ $b->redeemed_count }}</td>
                    <td class="ltr">{{ iqd($b->cost, false) }}</td>
                    <td class="ltr">{{ $b->created_at->format('Y/m/d') }}</td>
                    <td><a class="btn btn-plain btn-sm" href="{{ route('reseller.print', $b) }}" target="_blank">@include('partials.icon', ['n' => 'printer', 'class' => 'icon icon-sm']) طباعة</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="text-align:center;padding:28px">لا توجد دفعات بعد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $batches->links() }}
    <div style="height:64px"></div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var f = document.getElementById('issueForm'); if (!f) return;
  var disc = parseInt(f.dataset.discount, 10) || 0;
  var calc = function () {
    var a = parseInt((f.querySelector('[name=amount]:checked') || {}).value || 0, 10);
    var q = parseInt(f.querySelector('[name=quantity]').value || 0, 10);
    document.getElementById('cost').textContent = Math.round(a * q * (100 - disc) / 100).toLocaleString('en-US');
  };
  f.addEventListener('input', calc); f.addEventListener('change', calc); calc();
})();
</script>
@endpush
