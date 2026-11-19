@extends('layouts.app')
@section('title', $batch->name)

@section('content')
<div class="container">
    <div class="page-head row-between">
        <div>
            <h1>{{ $batch->name }}<span class="dot">.</span></h1>
            <p><span class="ltr">{{ $batch->quantity }}</span> قسيمة × <span class="ltr">{{ iqd($batch->amount, false) }}</span> د.ع</p>
        </div>
        <div class="row">
            <a class="btn btn-plain" href="{{ route('reseller.csv', $batch) }}">@include('partials.icon', ['n' => 'download']) Excel</a>
            <a class="btn btn-primary" href="{{ route('reseller.print', $batch) }}" target="_blank">@include('partials.icon', ['n' => 'printer']) طباعة الكروت</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>الكود</th><th>الحالة</th><th>بيعت</th><th>استخدمها</th><th></th></tr></thead>
            <tbody>
            @foreach ($vouchers as $v)
                <tr>
                    <td class="ltr">{{ $loop->iteration + ($vouchers->currentPage() - 1) * $vouchers->perPage() }}</td>
                    <td class="mono">{{ $v->formatted() }}</td>
                    <td><span @class(['badge', 'badge-green' => $v->status === 'active', 'badge-gray' => $v->status === 'redeemed', 'badge-red' => $v->status === 'disabled'])>{{ $v->statusLabel() }}</span></td>
                    <td>{{ $v->sold_at?->format('Y/m/d') ?? '—' }}</td>
                    <td>{{ $v->redeemer->name ?? '—' }}</td>
                    <td>
                        @if ($v->status === 'active')
                            <form method="POST" action="{{ route('reseller.sold', $v) }}">@csrf<button class="btn btn-plain btn-sm" type="submit">{{ $v->sold_at ? 'إلغاء البيع' : 'تعليم كمباعة' }}</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $vouchers->links() }}
    <div style="height:64px"></div>
</div>
@endsection
