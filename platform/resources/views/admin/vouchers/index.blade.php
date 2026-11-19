@extends('layouts.admin')
@section('heading', 'القسائم')
@section('sub', 'قسائم رصيد وقسائم كورسات، للبيع المباشر أو عبر الموزعين')
@section('actions')<a href="{{ route('admin.vouchers.create') }}" class="btn btn-gold btn-sm">@include('partials.icon', ['n' => 'plus', 'class' => 'icon icon-sm']) إصدار دفعة</a>@endsection

@section('content')
<div class="kpis">
    <div class="kpi"><span>قسائم فعالة</span><strong class="ltr">{{ number_format($stats['active']) }}</strong></div>
    <div class="kpi"><span>قيمتها</span><strong class="ltr">{{ iqd($stats['active_value'], false) }}</strong></div>
    <div class="kpi"><span>مستخدمة</span><strong class="ltr">{{ number_format($stats['redeemed']) }}</strong></div>
    <div class="kpi"><span>قيمة المستخدمة</span><strong class="ltr">{{ iqd($stats['redeemed_value'], false) }}</strong></div>
</div>

<form class="toolbar" action="{{ route('admin.vouchers.search') }}">
    <div class="search">@include('partials.icon', ['n' => 'search'])<input class="input mono" name="code" placeholder="ابحث بكود قسيمة" data-voucher></div>
    <button class="btn btn-plain" type="submit">بحث</button>
</form>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>الدفعة</th><th>النوع</th><th>القيمة</th><th>العدد</th><th>مستخدمة</th><th>الموزع</th><th>الصلاحية</th><th>التاريخ</th><th></th></tr></thead>
        <tbody>
        @forelse ($batches as $b)
            <tr>
                <td><a href="{{ route('admin.vouchers.show', $b) }}"><strong>{{ $b->name }}</strong></a></td>
                <td>@if ($b->course)<span class="badge badge-gold">كورس: {{ \Illuminate\Support\Str::limit($b->course->title, 18) }}</span>@else<span class="badge">رصيد</span>@endif</td>
                <td class="ltr">{{ iqd($b->amount, false) }}</td>
                <td class="ltr">{{ $b->vouchers_count }}</td>
                <td class="ltr">{{ $b->redeemed_count }}</td>
                <td>{{ $b->reseller->name ?? '—' }}</td>
                <td class="ltr">{{ $b->expires_at?->format('Y/m/d') ?? '—' }}</td>
                <td class="ltr">{{ $b->created_at->format('Y/m/d') }}</td>
                <td class="actions">
                    <a class="btn btn-plain btn-sm" href="{{ route('admin.vouchers.print', $b) }}" target="_blank">@include('partials.icon', ['n' => 'printer', 'class' => 'icon icon-sm'])</a>
                    <a class="btn btn-plain btn-sm" href="{{ route('admin.vouchers.csv', $b) }}">@include('partials.icon', ['n' => 'download', 'class' => 'icon icon-sm'])</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="muted" style="text-align:center;padding:28px">لا توجد قسائم بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $batches->links() }}
@endsection
