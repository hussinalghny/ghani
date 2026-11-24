@extends('layouts.app')
@section('description', 'غني أكاديمي: كورسات عملية بالأمن السيبراني وبرمجة المواقع والتطبيقات. 12 جلسة خلال 4 أسابيع، مدرب يشتغل بالمجال، ومشروع حقيقي وشهادة إتمام.')
@section('body_class', 'v4-landing')

@section('content')
@php($hero = $popular->first())
<section class="lp-hero">
    <div class="container lp-hero-in">
        <div class="lp-hero-txt">
            <span class="lp-pill">@include('partials.icon', ['n' => 'award']) مزود تدريب معتمد <span class="ltr">CPD</span></span>
            <h1>تعلم مهارة تقنية <br>تشتغل بيها خلال <span class="lp-hl"><span class="ltr">4</span> أسابيع</span></h1>
            <p>كورسات عملية بالأمن السيبراني وبرمجة المواقع والتطبيقات. مدرب يشتغل بالمجال، مشروع حقيقي، وشهادة إتمام قابلة للتحقق.</p>
            <div class="lp-cta">
                <a class="btn btn-primary btn-lg" href="{{ route('courses.index') }}">تصفح الكورسات</a>
                <a class="btn lp-btn-ghost btn-lg" href="{{ route('register') }}">إنشاء حساب مجاني</a>
            </div>
            <div class="lp-stats">
                <div><b class="ltr">{{ max(1, $stats['courses']) }}</b><span>كورسات</span></div>
                <div><b class="ltr">{{ $stats['lessons'] }}</b><span>درس</span></div>
                <div><b class="ltr">12</b><span>جلسة بكل كورس</span></div>
                @if ($stats['students'] >= 50)<div><b class="ltr">{{ number_format($stats['students']) }}</b><span>طالب</span></div>@endif
            </div>
        </div>
        <div class="lp-hero-art" aria-hidden="true">
            @if ($hero)
                <div class="lp-art-card">
                    <div class="u-im">@if ($hero->coverUrl())<img src="{{ $hero->coverUrl() }}" alt="">@endif</div>
                    <div class="lp-art-body">
                        <span class="u-new">{{ $hero->category?->name }}</span>
                        <h3>{{ $hero->title }}</h3>
                        <div class="u-bar"><i style="width:62%"></i></div>
                        <small>كمل من الدرس 7 · كشف الهجمات</small>
                    </div>
                </div>
            @endif
            <div class="lp-float lp-f1">@include('partials.icon', ['n' => 'award'])<div><b>شهادة إتمام</b><small>قابلة للتحقق</small></div></div>
            <div class="lp-float lp-f2">@include('partials.icon', ['n' => 'users'])<div><b>جاوبك المدرب</b><small>على سؤالك بالدرس 3</small></div></div>
        </div>
    </div>
</section>

@if ($categories->isNotEmpty())
<section class="container lp-sec">
    <div class="lp-head"><h2>شنو تتعلم ويانا</h2><p>ثلاث مسارات مطلوبة بسوق العمل، كل واحد يكتمل خلال شهر.</p></div>
    <div class="lp-cats">
        @foreach ($categories as $cat)
            <a class="lp-cat" href="{{ route('courses.category', $cat) }}">
                <span class="ic">@include('partials.icon', ['n' => $cat->icon ?: 'code'])</span>
                <b>{{ $cat->name }}</b>
                <small>@if ($cat->courses_count)<span class="ltr">{{ $cat->courses_count }}</span> {{ $cat->courses_count == 1 ? 'كورس' : 'كورسات' }}@else قريبا @endif</small>
            </a>
        @endforeach
    </div>
</section>
@endif

@if ($popular->isNotEmpty())
<section class="container lp-sec">
    <div class="u-sec" style="margin-top:0"><h2>الكورسات</h2><a href="{{ route('courses.index') }}">عرض الكل</a></div>
    <div class="u-grid">@foreach ($popular->take(4) as $course)@include('partials.ucard', ['course' => $course])@endforeach</div>
</section>
@endif

<section class="lp-band">
    <div class="container">
        <div class="lp-head"><h2>شلون تتعلم ويانا</h2><p>من أول درس لحد ما تستلم شهادتك.</p></div>
        <ol class="lp-steps">
            <li><span class="ltr">1</span><b>اختار كورسك</b><p>شوف المحتوى ودرس المعاينة قبل لا تسجل.</p></li>
            <li><span class="ltr">2</span><b>اتعلم من المدرب</b><p><span class="ltr">12</span> جلسة عملية خلال <span class="ltr">4</span> أسابيع، من الموبايل أو الكمبيوتر.</p></li>
            <li><span class="ltr">3</span><b>طبق بمشروع حقيقي</b><p>كل كورس ينتهي بمشروع تحطه بسيرتك الذاتية.</p></li>
            <li><span class="ltr">4</span><b>استلم شهادتك</b><p>شهادة إتمام برقم تحقق، يقدر أي صاحب عمل يتأكد منها.</p></li>
        </ol>
    </div>
</section>

<section class="container lp-sec">
    <div class="lp-head"><h2>ليش غني أكاديمي</h2><p>منصة مبنية حتى تتعلم صح، مو بس تتفرج.</p></div>
    <div class="lp-feats">
        <div>@include('partials.icon', ['n' => 'users'])<b>مدرب ممارس</b><p>كل مدرب يشتغل بالمجال اللي يدرسه، ويجاوب أسئلتك تحت كل درس.</p></div>
        <div>@include('partials.icon', ['n' => 'layers'])<b>عملي قبل النظري</b><p>تطبق بكل جلسة، وتطلع بمشروع حقيقي بنهاية الكورس.</p></div>
        <div>@include('partials.icon', ['n' => 'shield'])<b>محتوى محمي</b><p>المحاضرات محمية من التسريب، ورقمك يظهر فوق الفيديو.</p></div>
        <div>@include('partials.icon', ['n' => 'phone'])<b>تعلم من أي مكان</b><p>تطبيق للآيفون والأندرويد والكمبيوتر، وتكمل من وين ما وكفت.</p></div>
        <div>@include('partials.icon', ['n' => 'award'])<b>شهادة معتمدة</b><p>غني أكاديمي مزود تدريب معتمد من <span class="ltr">CPD Accreditation International</span>.</p></div>
        <div>@include('partials.icon', ['n' => 'wallet'])<b>دفع سهل</b><p>ادفع بالمحفظة الإلكترونية، والتفعيل يصير تلقائيا.</p></div>
    </div>
</section>

<section class="container lp-sec">
    <div class="lp-app">
        <div>
            <h2>المنصة بجيبك</h2>
            <p>نزل تطبيق غني أكاديمي وكمل دروسك من الموبايل، أو تطبيق الكمبيوتر لمشاهدة المحاضرات بأعلى حماية.</p>
            <a class="btn u-btn-w btn-lg" href="{{ route('app') }}">@include('partials.icon', ['n' => 'download']) حمل التطبيق</a>
        </div>
        <img src="{{ asset('img/logo-dark.png') }}" alt="" onerror="this.remove()">
    </div>
</section>

<section class="container lp-sec lp-faq-wrap">
    <div class="lp-head"><h2>أسئلة شائعة</h2></div>
    <div class="lp-faq">
        <details><summary>شكد مدة الكورس؟</summary><p>كل كورس <span class="ltr">4</span> أسابيع، بيه <span class="ltr">12</span> جلسة، وتقدر تشوف الدروس بأي وقت يناسبك.</p></details>
        <details><summary>أحتاج خبرة سابقة؟</summary><p>لا. الكورسات تبدأ من الأساسيات، ومكتوب بكل كورس مستواه وشنو تحتاج قبله.</p></details>
        <details><summary>الشهادة معتمدة؟</summary><p>تستلم شهادة إتمام من غني أكاديمي برقم تحقق. الأكاديمية مزود تدريب معتمد (<span class="ltr">Approved CPD Provider</span>).</p></details>
        <details><summary>شلون أدفع؟</summary><p>بالتحويل لمحفظة إلكترونية من صفحة الكورس، والنظام يأكد الدفع ويفعل الكورس تلقائيا.</p></details>
        <details><summary>أكدر أسأل المدرب؟</summary><p>إي. تحت كل درس أكو قسم أسئلة، والمدرب يجاوبك ويوصلك إشعار.</p></details>
    </div>
</section>

<section class="container lp-sec">
    <div class="lp-final">
        <h2>ابدأ اليوم، واشتغل بعد شهر</h2>
        <p>سجل حسابك مجانا وشوف درس المعاينة لأي كورس.</p>
        <div class="lp-cta" style="justify-content:center">
            <a class="btn btn-primary btn-lg" href="{{ route('register') }}">إنشاء حساب مجاني</a>
            <a class="btn lp-btn-ghost btn-lg" href="{{ route('courses.index') }}">تصفح الكورسات</a>
        </div>
    </div>
</section>
@endsection
