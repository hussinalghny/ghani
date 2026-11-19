@extends('layouts.app')
@section('title', $conversation->title())
@section('no_footer', true)

@section('content')
<div class="container container-sm chat-page">
    <div class="chat-head">
        <a href="{{ route('chat.index') }}" class="btn btn-plain btn-sm" aria-label="رجوع">@include('partials.icon', ['n' => 'chev-right', 'class' => 'icon icon-sm'])</a>
        <span class="cat-icon" style="width:42px;height:42px">@include('partials.icon', ['n' => $conversation->type === 'instructor' ? 'mic' : 'shield'])</span>
        <div><strong>{{ $conversation->title() }}</strong><div class="tiny">{{ $conversation->subtitle() }}</div></div>
    </div>
    @include('partials.chat-thread', ['emptyText' => $conversation->type === 'instructor' ? 'اكتب سؤالك عن الكورس، والمدرب يرد عليك هنا.' : 'اكتب مشكلتك أو سؤالك، وفريق الدعم يرد عليك هنا.'])
</div>
@endsection
