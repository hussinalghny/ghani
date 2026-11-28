@extends('layouts.admin')
@section('heading', 'تقرير المبيعات')
@section('sub', 'من '.$from->format('Y/m/d').' إلى '.$to->format('Y/m/d'))

@section('actions')
<a class="btn btn-plain btn-sm" href="{{ route('admin.reports.sales.csv', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">@include('partials.icon', ['n' => 'download', 'class' => 'icon icon-sm']) تصدير Excel</a>
@endsection

@section('content')
@php($q = fn ($f, $t) => route('admin.reports.sales', ['from' => $f->toDateString(), 'to' => $t->toDateString()]))
@php($now = now())
<form method="GET" class="panel row" style="gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:16px">
    <div class="field" style="margin:0"><label for="r-from">من</label><input class="input ltr" id="r-from" type="date" name="from" dir="ltr" value="{{ $from->toDateString() }}"></div>
    <div class="field" style="margin:0"><label for="r-to">إلى</label><input class="input ltr" id="r-to" type="date" name="to" dir="ltr" value="{{ $to->toDateString() }}"></div>
    <button class="btn btn-primary" type="submit">عرض</button>
    <span class="row" style="gap:6px;flex-wrap:wrap">
        <a class="btn btn-plain btn-sm" href="{{ $q($now->copy()->startOfDay(), $now) }}">اليوم</a>
        <a class="btn btn-plain btn-sm" href="{{ $q($now->copy()->subDays(6), $now) }}">آخر 7 أيام</a>
        <a class="btn btn-plain btn-sm" href="{{ $q($now->copy()->startOfMonth(), $now) }}">هذا الشهر</a>
        <a class="btn btn-plain btn-sm" href="{{ $q($now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()) }}">الشهر الماضي</a>
    </span>
</form>

<div class="kpis">
    <div class="kpi"><span>المبيعات</span><strong class="ltr">{{ number_format($stats['sales']) }}</strong><em><span class="ltr">{{ $stats['count'] }}</span> عملية</em></div>
    <div class="kpi"><span>كورسات إلكترونية</span><strong class="ltr">{{ number_format($stats['courses']) }}</strong></div>
    <div class="kpi"><span>مقاعد حضورية</span><strong class="ltr">{{ number_format($stats['seats']) }}</strong></div>
    <div class="kpi"><span>وصل للمحافظ (غني باي)</span><strong class="ltr">{{ number_format($stats['received']) }}</strong><em>قسائم: <span class="ltr">{{ number_format($stats['vouchers']) }}</span></em></div>
</div>

<div class="panel" style="margin-bottom:16px">
    <h2>المبيعات باليوم</h2>
    @php($max = max(1, $days->max('v')))
    <div class="chart">
        @foreach ($days as $p)
            <div @class(['b', 'today' => $p['d'] === now()->toDateString()]) style="height: {{ max(3, $p['v'] / $max * 100) }}%" title="{{ $p['d'] }} · {{ number_format($p['v']) }} د.ع"></div>
        @endforeach
    </div>
    <div class="row-between tiny muted ltr" style="margin-top:6px"><span>{{ $days->last()['d'] ?? '' }}</span><span>{{ $days->first()['d'] ?? '' }}</span></div>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:16px">
    <div class="panel">
        <h2>حسب الكورس</h2>
        @forelse ($byItem as $r)
            <div class="list-row"><span style="flex:1">{{ $r['item'] }} <span class="badge">{{ $r['kind'] }}</span></span><span class="tiny muted"><span class="ltr">{{ $r['count'] }}</span> ×</span><strong class="ltr">{{ number_format($r['sum']) }}</strong></div>
        @empty
            <p class="muted">ما أكو مبيعات بهالفترة.</p>
        @endforelse
    </div>
    <div class="panel">
        <h2>حسب المحفظة</h2>
        @forelse ($byMethod as $r)
            <div class="list-row"><span style="flex:1">{{ $r['label'] }}</span><span class="tiny muted"><span class="ltr">{{ $r['count'] }}</span> تحويل · تلقائي <span class="ltr">{{ $r['auto'] }}</span></span><strong class="ltr">{{ number_format($r['sum']) }}</strong></div>
        @empty
            <p class="muted">ما وصل تحويل بهالفترة.</p>
        @endforelse
        <p class="tiny muted" style="margin-top:8px">هذا المبلغ اللي وصل فعلا لمحافظك. الطالب ممكن يدفع جزء من رصيده، فيختلف عن المبيعات.</p>
    </div>
    <div class="panel">
        <h2>حسب كود الخصم</h2>
        @forelse ($byCode as $r)
            <div class="list-row"><span class="ltr" style="flex:1;text-align:right"><strong>{{ $r['code'] }}</strong></span><span class="tiny muted"><span class="ltr">{{ $r['count'] }}</span> ×</span><strong class="ltr">{{ number_format($r['sum']) }}</strong></div>
        @empty
            <p class="muted">ما انستخدم كود بهالفترة.</p>
        @endforelse
    </div>
</div>

<div class="panel">
    <h2>آخر العمليات</h2>
    <div class="table-wrap" style="border:0">
        <table class="table">
            <thead><tr><th>الوقت</th><th>الطالب</th><th>الهاتف</th><th>الكورس</th><th>المبلغ</th><th>الكود</th></tr></thead>
            <tbody>
            @forelse ($recent as $l)
                <tr>
                    <td class="tiny ltr">{{ $l['at']->format('Y/m/d H:i') }}</td>
                    <td>{{ $l['name'] }}</td>
                    <td class="ltr">{{ $l['phone'] }}</td>
                    <td>{{ $l['item'] }} <span class="badge">{{ $l['kind'] }}</span></td>
                    <td class="ltr">{{ number_format($l['amount']) }}</td>
                    <td class="ltr">{{ $l['code'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">ما أكو عمليات.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
