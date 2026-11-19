@extends('layouts.admin')
@section('heading', $conversation->user->name)
@section('sub')<span class="ltr">{{ $conversation->user->phone }}</span> · {{ $conversation->type === 'instructor' ? 'سؤال عن '.($conversation->course->title ?? 'كورس') : 'الدعم' }}@endsection
@section('actions')
    <a class="btn btn-plain btn-sm" href="{{ route('inbox') }}">كل المحادثات</a>
    @if (auth()->user()->isAdmin())<a class="btn btn-plain btn-sm" href="{{ route('admin.students.show', $conversation->user) }}">ملف الطالب</a>@endif
@endsection

@section('content')
<div class="chat-page" style="max-width:820px">
    @include('partials.chat-thread', ['emptyText' => 'لا توجد رسائل.'])
</div>
@endsection
