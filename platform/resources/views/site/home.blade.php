@extends('layouts.app')

@php($top = $featured->first() ?? $latest->first())
@php($courses = $featured->isNotEmpty() ? $featured : $latest)

@section('content')
<section class="x-hero">
    <div class="container">
        <div>
            <span class="x-pill">{{ setting('home_badge') ?: 'التسجيل مفتوح · كورسات تقنية عملية' }}</span>
            <h1>تعلم. طبق.<br>اشتغل<span class="dot">.</span></h1>
            <p class="lead">كورسات عملية بمدربين يشتغلون بالمجال. <span class="ltr">12</span> جلسة خلال <span class="ltr">4</span> أسابيع، مشروع حقيقي، وشهادة إتمام.</p>
            <div class="x-actions">
                <a href="{{ route('courses.index') }}" class="btn btn-primary btn-lg">تصفح الكورسات</a>
                <a href="{{ route('app') }}" class="btn btn-outline btn-lg">@include('partials.icon', ['n' => 'dashboard', 'class' => 'icon icon-sm']) حمل تطبيق الكمبيوتر</a>
            </div>
            <div class="x-stats">
                <div><strong class="ltr">{{ max($stats['courses'], 1) }}</strong><span>كورسات متاحة</span></div>
                <div><strong class="ltr">12</strong><span>جلسة لكل كورس</span></div>
                <div><strong>حضوري</strong><span>أو إلكتروني</span></div>
            </div>
        </div>
        <div class="x-visual">
            <div class="shot">
                @if (setting('home_hero_image'))
                    <img src="{{ asset('storage/'.setting('home_hero_image')) }}" alt="">
                @elseif ($top?->coverUrl())
                    <img src="{{ $top->coverUrl() }}" alt="">
                @endif
            </div>
            @if ($top)
                <a class="x-float" href="{{ route('courses.show', $top) }}">
                    <span class="k">{{ $top->is_featured ? 'الكورس الأكثر طلبا' : 'أحدث كورس' }}</span>
                    <strong>{{ $top->title }}<span class="dot">.</span></strong>
                    @if ($top->subtitle)<span class="small muted">{{ \Illuminate\Support\Str::limit($top->subtitle, 60) }}</span>@endif
                    <span class="row-b"><span><span class="ltr">{{ $top->lessons_count }}</span> درس</span><b>@if ($top->isFree())مجاني@else<span class="ltr">{{ iqd($top->finalPrice(), false) }}</span> د.ع@endif</b></span>
                </a>
            @endif
        </div>
    </div>
</section>

@if ($categories->isNotEmpty())
<section class="container" style="padding-top:16px">
    <div class="x-chips">
        <a class="x-chip on" href="{{ route('courses.index') }}">الكل</a>
        @foreach ($categories as $cat)
            <a class="x-chip" href="{{ route('courses.category', $cat) }}">@include('partials.icon', ['n' => $cat->icon]) {{ $cat->name }}</a>
        @endforeach
    </div>
</section>
@endif

@if ($courses->isNotEmpty())
<section class="x-sec" style="padding-top:56px">
    <div class="container">
        <div class="x-head">
            <div>
                <h2>اختر مسارك<span class="dot">.</span></h2>
                <p>كل كورس شهر واحد، وينتهي بمشروع تضيفه لأعمالك.</p>
            </div>
            <a href="{{ route('courses.index') }}" class="link">كل الكورسات @include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</a>
        </div>
        <div class="x-grid3">
            @foreach ($courses->take(6) as $course)
                @include('partials.course-card', ['course' => $course])
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="x-sec alt">
    <div class="container x-center">
        <h2 class="x-title">من أول جلسة إلى أول شغل<span class="dot">.</span></h2>
        <p>عملي قبل النظري. كل أسبوع تطبق شي حقيقي، وبالأسبوع الرابع تسلم مشروعك.</p>
        <div class="x-grid4">
            <div class="x-step"><b class="ltr">01</b><h3>اختر كورسك</h3><p>ادفع من محفظتك أو فعل قسيمة من موزع معتمد.</p></div>
            <div class="x-step"><b class="ltr">02</b><h3>احضر وطبق</h3><p><span class="ltr">12</span> جلسة بساعتين، حضوري أو من تطبيق الكمبيوتر.</p></div>
            <div class="x-step"><b class="ltr">03</b><h3>اسأل مدربك</h3><p>محادثة مباشرة ويا المدرب والدعم من داخل المنصة.</p></div>
            <div class="x-step"><b class="ltr">04</b><h3>استلم شهادتك</h3><p>شهادة إتمام برقم تحقق، ومشروع تضيفه لأعمالك.</p></div>
        </div>
    </div>
</section>

<section class="x-sec">
    <div class="container x-app">
        <div class="x-window" aria-hidden="true">
            <div class="bar"><i></i><i></i><i></i></div>
            <div class="screen"><div>
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#F5B83D" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
                <span>بأي تسجيل شاشة: الصورة تطلع سودة</span>
            </div></div>
        </div>
        <div>
            <span class="x-pill">تطبيق غني أكاديمي للكمبيوتر</span>
            <h2 class="x-title" style="margin-top:18px">محاضراتك محمية<span class="dot">.</span></h2>
            <p class="muted" style="font-size:18px;line-height:1.8">الدروس تشتغل من تطبيق الكمبيوتر حتى يبقى المحتوى لك وحدك. نفس حسابك، ونفس تقدمك بكل كورس.</p>
            <div class="x-check"><i>✓</i>الشاشة تطلع سودة بأي تسجيل أو سكرين شوت</div>
            <div class="x-check"><i>✓</i>علامة مائية برقم حسابك على الفيديو</div>
            <div class="x-check"><i>✓</i>يكمل من نفس الدقيقة اللي وقفت عليها</div>
            <div class="x-actions" style="align-items:center;margin-top:20px">
                <a href="{{ route('app') }}" class="btn btn-primary btn-lg">تنزيل لويندوز</a>
                <span class="small faint"><span class="ltr">Windows 10 / 11</span> · بدون صلاحيات مدير</span>
            </div>
        </div>
    </div>
</section>

<section class="container" style="padding-bottom:96px">
    <div class="x-band">
        <div>
            <h2>عندك قسيمة رصيد<span class="dot">؟</span></h2>
            <p>اشتريتها من موزع معتمد؟ فعلها هنا وينضاف رصيدها لمحفظتك فورا.</p>
        </div>
        @auth
            <form method="POST" action="{{ route('wallet.redeem') }}">
                @csrf
                <label for="hcode" class="sr-only">رمز القسيمة</label>
                <input id="hcode" name="code" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="XXXX-XXXX-XXXX" data-voucher required>
                <button class="btn btn-lg" type="submit">تفعيل</button>
            </form>
        @else
            <div class="x-actions">
                <a href="{{ route('login') }}" class="btn btn-lg" style="background:#F5B83D;color:#1B1446">سجل دخول وفعل قسيمتك</a>
                <a href="https://wa.me/964{{ ltrim(config('academy.whatsapp'), '0') }}" class="btn btn-light btn-lg" target="_blank" rel="noopener">اسأل عن أقرب موزع</a>
            </div>
        @endauth
    </div>
</section>
@endsection
