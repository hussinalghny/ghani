@extends('layouts.app')
@section('title', 'الرسائل')

@section('content')
<div class="container container-sm">
    <div class="page-head row-between">
        <div><h1>الرسائل<span class="dot">.</span></h1><p>تكلم ويا الدعم أو مدرب كورسك مباشرة.</p></div>
        <a class="btn btn-primary" href="{{ route('chat.support') }}">@include('partials.icon', ['n' => 'whatsapp']) راسل الدعم</a>
    </div>
    @forelse ($conversations as $c)
        <a href="{{ route('chat.show', $c) }}" class="panel list-row anim-in" style="color:inherit;margin-bottom:12px;padding:16px 20px">
            <span class="cat-icon">@include('partials.icon', ['n' => $c->type === 'instructor' ? 'mic' : 'shield'])</span>
            <span style="flex:1;min-width:0">
                <strong style="display:block">{{ $c->title() }}</strong>
                <span class="tiny" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c->lastMessage->body ?? $c->subtitle() }}</span>
            </span>
            @if ($c->student_unread)<span class="badge ltr">{{ $c->student_unread }}</span>@endif
        </a>
    @empty
        <div class="empty">
            <div class="ic">@include('partials.icon', ['n' => 'whatsapp'])</div>
            <h3>ما عندك محادثات بعد<span class="dot">.</span></h3>
            <p>للسؤال عن كورس افتح صفحته واضغط «اسأل المدرب».</p>
            <a class="btn btn-primary" href="{{ route('chat.support') }}">راسل الدعم</a>
        </div>
    @endforelse
    <div style="height:64px"></div>
</div>
@endsection
