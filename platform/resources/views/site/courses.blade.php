@extends('layouts.app')
@section('title', $category->name ?? 'الكورسات')

@section('content')
<div class="container">
    <div class="page-head">
        <h1>{{ $category->name ?? 'كل الكورسات' }}<span class="dot">.</span></h1>
        <p>{{ $category->description ?? 'اختر الكورس المناسب وابدأ اليوم.' }}</p>
    </div>

    <div class="toolbar">
        <form class="search" action="{{ $category ? route('courses.category', $category) : route('courses.index') }}" role="search">
            @include('partials.icon', ['n' => 'search'])
            <label class="sr-only" for="q">ابحث</label>
            <input class="input" id="q" type="search" name="q" value="{{ $q }}" placeholder="ابحث عن كورس">
        </form>
        <div class="pills">
            <a href="{{ route('courses.index') }}" @class(['pill', 'active' => ! $category])>الكل</a>
            @foreach ($categories as $cat)
                <a href="{{ route('courses.category', $cat) }}" @class(['pill', 'active' => $category?->id === $cat->id])>{{ $cat->name }}</a>
            @endforeach
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="empty">
            <div class="ic">@include('partials.icon', ['n' => 'search'])</div>
            <h3>ما لقينا كورسات<span class="dot">.</span></h3>
            <p>جرب كلمة ثانية أو تصفح كل الأقسام.</p>
            <a href="{{ route('courses.index') }}" class="btn btn-ghost">كل الكورسات</a>
        </div>
    @else
        <div class="grid grid-3">
            @foreach ($courses as $course)
                @include('partials.course-card', ['course' => $course])
            @endforeach
        </div>
        {{ $courses->links() }}
    @endif
    <div style="height:64px"></div>
</div>
@endsection
