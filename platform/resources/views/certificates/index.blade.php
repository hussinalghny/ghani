@extends('layouts.app')
@section('title', 'شهاداتي')

@section('content')
<div class="container container-sm">
    <div class="page-head">
        <h1>شهاداتي<span class="dot">.</span></h1>
        <p>تصدر الشهادة تلقائيا لما تكمل كل دروس الكورس.</p>
    </div>
    @forelse ($certificates as $c)
        <a href="{{ route('certificates.show', $c) }}" class="panel list-row" style="color:inherit;margin-bottom:12px;padding:18px 22px">
            <span class="cat-icon">@include('partials.icon', ['n' => 'award'])</span>
            <span style="flex:1"><strong style="display:block">{{ $c->course->title }}</strong><span class="tiny"><span class="mono">{{ $c->code }}</span> · {{ $c->issued_at->format('Y/m/d') }}</span></span>
            @include('partials.icon', ['n' => 'chev-left'])
        </a>
    @empty
        <div class="empty">
            <div class="ic">@include('partials.icon', ['n' => 'award'])</div>
            <h3>لا توجد شهادات بعد<span class="dot">.</span></h3>
            <p>أكمل دروس أي كورس وتصدر شهادتك هنا.</p>
            <a href="{{ route('account') }}" class="btn btn-primary">كورساتي</a>
        </div>
    @endforelse
    <div style="height:64px"></div>
</div>
@endsection
