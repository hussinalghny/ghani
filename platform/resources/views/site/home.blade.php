@extends('layouts.app')

@section('content')
<section class="hero">
    <div class="container">
        <span class="eyebrow">غني أكاديمي · كورسات تقنية عملية</span>
        <h1>تعلم. طبق. اشتغل<span class="dot">.</span></h1>
        <p class="lead">كورسات تقنية بمدربين ممارسين، كل كورس خلال 4 أسابيع، وتطلع منه بمشروع حقيقي وشهادة إتمام.</p>
        <div class="hero-actions">
            <a href="{{ route('courses.index') }}" class="btn btn-primary">تصفح الكورسات</a>
            @guest
                <a href="{{ route('register') }}" class="btn btn-ghost">أنشئ حسابك مجانا</a>
            @else
                <a href="{{ route('account') }}" class="btn btn-ghost">أكمل التعلم</a>
            @endguest
        </div>

        <div class="hero-stage" aria-hidden="true">
            <div class="stage-card">
                <div class="stage-video">
                    <span class="wm ltr">{{ auth()->user()?->phone ?? '07XX XXX XXXX' }}</span>
                    <div class="play">@include('partials.icon', ['n' => 'play'])</div>
                </div>
                <div class="stage-meta">
                    <strong class="small">الجلسة 5 · تحليل الثغرات</strong>
                    <div class="bar"><span></span></div>
                    <span class="tiny ltr">62%</span>
                </div>
            </div>
        </div>

        <div class="stats">
            <div class="stat"><strong class="ltr">{{ max($stats['courses'], 3) }}</strong><span>كورسات متاحة</span></div>
            <div class="stat"><strong class="ltr">12</strong><span>جلسة لكل كورس</span></div>
            <div class="stat"><strong class="ltr">4</strong><span>أسابيع للإتمام</span></div>
        </div>
    </div>
</section>

@if ($categories->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="head">
            <h2>اختر مجالك<span class="dot">.</span></h2>
            <p>كل مجال مقسم إلى كورسات قصيرة وواضحة، تبدأ من الصفر.</p>
        </div>
        <div class="grid grid-4">
            @foreach ($categories as $cat)
                <a href="{{ route('courses.category', $cat) }}" class="cat-card">
                    <span class="cat-icon">@include('partials.icon', ['n' => $cat->icon])</span>
                    <h3>{{ $cat->name }}</h3>
                    @if ($cat->description)<p>{{ $cat->description }}</p>@endif
                    <span class="count"><span class="ltr">{{ $cat->courses_count }}</span> كورس</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if ($featured->isNotEmpty())
<section class="section section-alt">
    <div class="container">
        <div class="head-row">
            <h2>كورسات مميزة<span class="dot">.</span></h2>
            <a href="{{ route('courses.index') }}" class="link">عرض الكل @include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</a>
        </div>
        <div class="grid grid-3">
            @foreach ($featured as $course)
                @include('partials.course-card', ['course' => $course])
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section">
    <div class="container">
        <div class="head">
            <h2>عملي قبل النظري<span class="dot">.</span></h2>
            <p>أربع قيم نبني عليها كل كورس.</p>
        </div>
        <div class="grid grid-4">
            <div class="feature"><span class="num ltr">01</span><h3>عملي قبل النظري</h3><p>كل جلسة تنتهي بشيء تعمله بيدك.</p></div>
            <div class="feature"><span class="num ltr">02</span><h3>مدرب ممارس</h3><p>المدربون يشتغلون في المجال نفسه.</p></div>
            <div class="feature"><span class="num ltr">03</span><h3>مشروع حقيقي</h3><p>تخرج بمشروع تقدر تعرضه لصاحب العمل.</p></div>
            <div class="feature"><span class="num ltr">04</span><h3>شهر واحد</h3><p>مدة قصيرة وخطة واضحة وشهادة إتمام.</p></div>
        </div>
    </div>
</section>

@if ($latest->isNotEmpty())
<section class="section section-alt">
    <div class="container">
        <div class="head-row">
            <h2>أحدث الكورسات<span class="dot">.</span></h2>
            <a href="{{ route('courses.index') }}" class="link">كل الكورسات @include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</a>
        </div>
        <div class="grid grid-4">
            @foreach ($latest->take(4) as $course)
                @include('partials.course-card', ['course' => $course])
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section">
    <div class="container">
        <div class="dark-band">
            <span class="eyebrow">قسائم الرصيد</span>
            <h2>اشحن رصيدك بقسيمة<span class="dot">.</span></h2>
            <p>اشتر قسيمة من أقرب موزع معتمد، اكتب الكود بالمحفظة، وافتح أي كورس بضغطة.</p>
            <div class="hero-actions" style="justify-content:flex-start">
                <a href="{{ route('wallet') }}" class="btn btn-gold">اشحن الآن</a>
                <a href="https://wa.me/964{{ ltrim(config('academy.whatsapp'), '0') }}" class="btn btn-light" target="_blank" rel="noopener">اسأل عن أقرب موزع</a>
            </div>
        </div>
    </div>
</section>
@endsection
