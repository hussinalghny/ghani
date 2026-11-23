@extends('layouts.app')

@php($top = $featured->first() ?? $latest->first())
@php($courses = ($featured->isNotEmpty() ? $featured : $latest)->take(6))

@section('content')
<section class="container ga-home">
    @php($d = now()->timezone('Asia/Baghdad'))
    <div class="ga-eyebrow">{{ ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'][$d->dayOfWeek] }}، <span class="ltr">{{ $d->day }}</span> {{ ['','كانون الثاني','شباط','آذار','نيسان','أيار','حزيران','تموز','آب','أيلول','تشرين الأول','تشرين الثاني','كانون الأول'][$d->month] }}</div>
    <h1 class="ga-title">اكتشف<span class="dot">.</span></h1>

    <div class="ga-hero-grid">
        @if ($top)
            <a class="ga-hero" href="{{ route('courses.show', $top) }}">
                @if (setting('home_hero_image'))<img src="{{ asset('storage/'.setting('home_hero_image')) }}" alt="">@elseif ($top->coverUrl())<img src="{{ $top->coverUrl() }}" alt="">@endif
                <div class="body">
                    <span class="tag">{{ $top->is_featured ? 'الأكثر طلبا' : 'أحدث كورس' }}</span>
                    <h2>{{ $top->title }}</h2>
                    @if ($top->subtitle)<p>{{ \Illuminate\Support\Str::limit($top->subtitle, 80) }}</p>@endif
                    <div class="row">
                        <span class="pill-gold">اكتشف الكورس</span>
                        <b>@if ($top->isFree())مجاني@else<span class="ltr">{{ iqd($top->finalPrice(), false) }}</span> د.ع@endif</b>
                    </div>
                </div>
            </a>
        @endif
        <div class="ga-side">
            <div class="ga-banner">
                <h3>تعلم. طبق. اشتغل<span class="dot">.</span></h3>
                <p>كل كورس شهر واحد، <span class="ltr">12</span> جلسة، ومشروع حقيقي تضيفه لأعمالك.</p>
                <a class="pill-gold" href="{{ route('courses.index') }}">تصفح الكورسات</a>
            </div>
            <div class="ga-group">
                @auth
                    <a class="ga-cell" href="{{ route('wallet') }}"><span class="ic ic-gold">@include('partials.icon', ['n' => 'wallet'])</span><span class="t"><b>رصيدك <span class="ltr">{{ iqd(auth()->user()->balance, false) }}</span> د.ع</b><small>اشحن بسوبر كي أو بقسيمة</small></span><span class="chev">@include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</span></a>
                    <a class="ga-cell" href="{{ route('account') }}"><span class="ic ic-violet">@include('partials.icon', ['n' => 'play'])</span><span class="t"><b>مساري</b><small>كمل من حيث وقفت</small></span><span class="chev">@include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</span></a>
                @else
                    <a class="ga-cell" href="{{ route('register') }}"><span class="ic ic-violet">@include('partials.icon', ['n' => 'user'])</span><span class="t"><b>سوي حسابك</b><small>دقيقة وحدة برقم موبايلك</small></span><span class="chev">@include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</span></a>
                    <a class="ga-cell" href="{{ route('login') }}"><span class="ic ic-night">@include('partials.icon', ['n' => 'play'])</span><span class="t"><b>عندي حساب</b><small>سجل دخول وكمل مسارك</small></span><span class="chev">@include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</span></a>
                @endauth
            </div>
        </div>
    </div>

    @if ($categories->isNotEmpty())
        <div class="ga-sec"><h3>المسارات</h3></div>
        <div class="ga-chips">
            @foreach ($categories as $cat)
                <a class="ga-chip" href="{{ route('courses.category', $cat) }}">@include('partials.icon', ['n' => $cat->icon]) {{ $cat->name }}</a>
            @endforeach
        </div>
    @endif

    @if ($courses->isNotEmpty())
        <div class="ga-sec"><h3>كورسات مختارة</h3><a href="{{ route('courses.index') }}">عرض الكل</a></div>
        <div class="ga-grid">
            @foreach ($courses as $course)
                @include('partials.course-card', ['course' => $course])
            @endforeach
        </div>
    @endif
</section>
@endsection
