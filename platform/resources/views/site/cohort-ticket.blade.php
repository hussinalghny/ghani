@extends('layouts.app')
@section('title', 'تذكرة · '.$r->cohort->title)
@section('body_class', 'v4-ticket')

@section('content')
@php($months = ['', 'كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'])
<div class="container container-sm" style="padding-top:32px;padding-bottom:80px">
    <div class="tk">
        <div class="tk-top">
            <img src="{{ asset('img/logo-dark.png') }}" alt="Ghani Academy">
            <span class="badge {{ $r->status === 'paid' ? 'badge-green' : 'badge-gold' }}">{{ \App\Models\CohortRegistration::STATUSES[$r->status] ?? $r->status }}</span>
        </div>
        <div class="tk-body">
            <small>كورس حضوري</small>
            <h1>{{ $r->cohort->title }}</h1>
            <div class="tk-grid">
                <div><span>الاسم</span><b>{{ $r->name }}</b></div>
                <div><span>الهاتف</span><b class="ltr">{{ $r->phone }}</b></div>
                <div><span>يبدأ</span><b>{{ $r->cohort->starts_on ? $r->cohort->starts_on->day.' '.$months[$r->cohort->starts_on->month].' '.$r->cohort->starts_on->year : 'يحدد قريبا' }}</b></div>
                <div><span>المكان</span><b>{{ $r->cohort->location ?: 'بغداد' }}</b></div>
                @if ($r->cohort->schedule)<div style="grid-column:1/-1"><span>الأوقات</span><b>{{ $r->cohort->schedule }}</b></div>@endif
            </div>
        </div>
        <div class="tk-cut"></div>
        <div class="tk-code">
            <div id="qr" aria-hidden="true"></div>
            <div><span>رمز التذكرة</span><b class="ltr">{{ $r->code }}</b><small>اعرض هذا الرمز عند الدخول</small></div>
        </div>
    </div>
    <div class="row" style="justify-content:center;gap:10px;margin-top:18px">
        @if ($r->status === 'pending')<a class="btn btn-primary" href="{{ route('cohort.show', $r->cohort) }}#pay">كمل الدفع</a>@endif
        <button class="btn btn-plain" type="button" onclick="window.print()">@include('partials.icon', ['n' => 'printer']) طباعة</button>
        <a class="btn btn-plain" href="{{ route('cohort.show', $r->cohort) }}">صفحة الكورس</a>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<script>
addEventListener('load', function () {
    try { new QRCode(document.getElementById('qr'), { text: @json(route('cohort.ticket', $r)), width: 112, height: 112, colorDark: '#1B1446', colorLight: '#ffffff' }); }
    catch (e) { document.getElementById('qr').remove(); }
});
</script>
@endsection
