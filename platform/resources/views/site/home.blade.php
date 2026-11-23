@extends('layouts.app')
@section('body_class', 'v4-home')

@section('content')
<section class="container u-home">
    <form class="u-search" action="{{ route('courses.index') }}" role="search">
        @include('partials.icon', ['n' => 'search'])
        <label class="sr-only" for="hq">ابحث</label>
        <input id="hq" type="search" name="q" placeholder="ابحث عن كورس أو مهارة">
    </form>

    @if ($categories->isNotEmpty())
        <div class="u-chips">
            <a class="on" href="{{ route('courses.index') }}">الكل</a>
            @foreach ($categories as $cat)<a href="{{ route('courses.category', $cat) }}">{{ $cat->name }}</a>@endforeach
        </div>
    @endif

    @if ($continue)
        <div class="u-sec"><h2>كمل التعلم</h2><a href="{{ route('account') }}">الكل</a></div>
        <a class="u-cont" href="{{ route('learn.show', [$continue['course'], $continue['lesson']]) }}">
            <span class="u-im">@if ($continue['course']->coverUrl())<img src="{{ $continue['course']->thumbUrl() }}" alt="">@endif</span>
            <span class="t"><b>{{ $continue['course']->title }}</b><small>{{ $continue['lesson']->title }}</small><span class="u-bar"><i style="width:{{ $continue['progress'] }}%"></i></span></span>
            <span class="u-play">@include('partials.icon', ['n' => 'play'])</span>
        </a>
    @endif

    <div class="u-ban">
        <span class="k">غني أكاديمي</span>
        <h2>ابدأ مهنتك الجديدة خلال <span class="ltr">4</span> أسابيع</h2>
        <p><span class="ltr">12</span> جلسة عملية، مشروع حقيقي، وشهادة إتمام.</p>
        <a class="btn u-btn-w" href="{{ route('courses.index') }}">تصفح الكورسات</a>
    </div>

    @if ($popular->isNotEmpty())
        <div class="u-sec"><h2>الأكثر تسجيلا</h2><a href="{{ route('courses.index') }}">عرض الكل</a></div>
        <div class="u-row">
            @foreach ($popular as $course)@include('partials.ucard', ['course' => $course])@endforeach
        </div>
    @endif

    @if ($latest->isNotEmpty())
        <div class="u-sec"><h2>جديد بالأكاديمية</h2><a href="{{ route('courses.index') }}">عرض الكل</a></div>
        <div class="u-list">
            @foreach ($latest->take(6) as $course)
                <a class="u-li" href="{{ route('courses.show', $course) }}">
                    <span class="u-im">@if ($course->coverUrl())<img src="{{ $course->thumbUrl() }}" alt="" loading="lazy">@endif</span>
                    <span class="t"><h4>{{ $course->title }}</h4>@include('partials.stars', ['course' => $course])<span class="u-price">@if ($course->isFree())مجاني@else<span class="ltr">{{ iqd($course->finalPrice(), false) }}</span> د.ع@endif</span></span>
                </a>
            @endforeach
        </div>
    @endif
</section>
@endsection
