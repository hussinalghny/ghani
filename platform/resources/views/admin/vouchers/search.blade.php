@extends('layouts.admin')
@section('heading', 'البحث عن قسيمة')

@section('content')
<form class="toolbar" action="{{ route('admin.vouchers.search') }}">
    <div class="search">@include('partials.icon', ['n' => 'search'])<input class="input mono" name="code" value="{{ $code }}" placeholder="XXXX-XXXX-XXXX" data-voucher autofocus></div>
    <button class="btn btn-primary" type="submit">بحث</button>
</form>
@if ($code && ! $voucher)
    <div class="alert warn">ما لقينا قسيمة بهذا الكود.</div>
@elseif ($voucher)
    <div class="panel" style="max-width:640px">
        <div class="row-between"><span class="mono" style="font-size:20px">{{ $voucher->formatted() }}</span><span class="badge">{{ $voucher->statusLabel() }}</span></div>
        <div class="list-row"><span style="flex:1" class="muted">القيمة</span><span class="ltr">{{ iqd($voucher->amount) }}</span></div>
        <div class="list-row"><span style="flex:1" class="muted">النوع</span><span>{{ $voucher->course ? 'كورس: '.$voucher->course->title : 'رصيد' }}</span></div>
        <div class="list-row"><span style="flex:1" class="muted">الدفعة</span><a href="{{ route('admin.vouchers.show', $voucher->batch) }}">{{ $voucher->batch->name }}</a></div>
        <div class="list-row"><span style="flex:1" class="muted">الموزع</span><span>{{ $voucher->reseller->name ?? '—' }}</span></div>
        <div class="list-row"><span style="flex:1" class="muted">استخدمها</span><span>{{ $voucher->redeemer ? $voucher->redeemer->name.' · '.$voucher->redeemer->phone : '—' }}</span></div>
        <div class="list-row"><span style="flex:1" class="muted">وقت الاستخدام</span><span class="ltr">{{ $voucher->redeemed_at?->format('Y/m/d H:i') ?? '—' }}</span></div>
    </div>
@endif
@endsection
