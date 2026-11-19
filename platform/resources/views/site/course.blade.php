@extends('layouts.app')
@section('title', $course->title)
@section('description', $course->subtitle)

@section('content')
<section class="course-hero">
    <div class="container course-hero-grid">
        <div>
            <div class="crumbs">
                <a href="{{ route('courses.index') }}">الكورسات</a>
                @include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])
                <a href="{{ route('courses.category', $course->category) }}">{{ $course->category->name }}</a>
            </div>
            <h1>{{ $course->title }}<span class="dot">.</span></h1>
            @if ($course->subtitle)<p class="lead">{{ $course->subtitle }}</p>@endif
            <div class="chips">
                <span class="chip">@include('partials.icon', ['n' => 'play-circle']) <span class="ltr">{{ $lessonsCount }}</span> درس</span>
                @if ($totalDuration)<span class="chip">@include('partials.icon', ['n' => 'clock']) {{ duration_human($totalDuration) }}</span>@endif
                @if ($course->duration_label)<span class="chip">@include('partials.icon', ['n' => 'layers']) {{ $course->duration_label }}</span>@endif
                <span class="chip">@include('partials.icon', ['n' => 'chart']) {{ $course->levelLabel() }}</span>
                @if ($course->has_certificate)<span class="chip">@include('partials.icon', ['n' => 'award']) شهادة إتمام</span>@endif
            </div>
        </div>

        <aside class="buy-card">
            @include('partials.cover', ['course' => $course, 'showTag' => false])
            <div class="buy-body">
                @if ($enrolled)
                    <div class="row-between" style="margin-bottom:10px"><strong>تقدمك</strong><span class="ltr">{{ $progress }}%</span></div>
                    <div class="progress" style="margin-bottom:18px"><span style="width: {{ $progress }}%"></span></div>
                    <a href="{{ route('learn.show', $course) }}" class="btn btn-primary btn-block">{{ $progress ? 'أكمل من حيث توقفت' : 'ابدأ الدرس الأول' }}</a>
                @else
                    <div class="buy-price">
                        @if ($course->isFree()) مجاني
                        @else <span class="ltr">{{ iqd($course->finalPrice(), false) }}</span> د.ع
                            @if ($course->finalPrice() < $course->price)<small class="ltr">{{ iqd($course->price, false) }}</small>@endif
                        @endif
                    </div>
                    @auth
                        <p class="small muted" style="margin:6px 0 16px">رصيدك: <span class="ltr">{{ iqd(auth()->user()->balance, false) }}</span> د.ع</p>
                        @if (auth()->user()->balance >= $course->finalPrice())
                            <form method="POST" action="{{ route('courses.enroll', $course) }}" data-once data-confirm="{{ $course->isFree() ? 'تسجيل في الكورس؟' : 'سيتم خصم '.iqd($course->finalPrice()).' من رصيدك. متأكد؟' }}">
                                @csrf
                                <button class="btn btn-gold btn-block" type="submit">{{ $course->isFree() ? 'سجل مجانا' : 'اشترك الآن' }}</button>
                            </form>
                        @else
                            <a href="{{ route('wallet') }}" class="btn btn-gold btn-block">اشحن رصيدك للاشتراك</a>
                            <p class="tiny" style="margin:10px 0 0;text-align:center">تحتاج <span class="ltr">{{ iqd($course->finalPrice() - auth()->user()->balance, false) }}</span> د.ع إضافية. عندك قسيمة؟ اكتبها بالمحفظة.</p>
                        @endif
                    @else
                        <p class="small muted" style="margin:6px 0 16px">سجل حسابك واشحن رصيدك بقسيمة للاشتراك.</p>
                        <a href="{{ route('register') }}" class="btn btn-gold btn-block">احجز مقعدك</a>
                        <a href="{{ route('login') }}" class="btn btn-plain btn-block" style="margin-top:8px">عندي حساب</a>
                    @endauth
                @endif
                <ul class="checks">
                    <li>@include('partials.icon', ['n' => 'check']) وصول {{ $course->access_days ? 'لمدة '.$course->access_days.' يوم' : 'دائم' }} للدروس</li>
                    <li>@include('partials.icon', ['n' => 'check']) مشاهدة من الموقع والتطبيق</li>
                    @if ($course->has_certificate)<li>@include('partials.icon', ['n' => 'check']) شهادة إتمام قابلة للتحقق</li>@endif
                </ul>
            </div>
        </aside>
    </div>
</section>

<section class="section" style="padding-top:48px">
    <div class="container content-grid">
        <div class="stack" style="--gap:32px">
            @if ($course->outcomesList())
                <div>
                    <h2 style="font-size:26px">ماذا ستتعلم<span class="dot">.</span></h2>
                    <ul class="outcomes">
                        @foreach ($course->outcomesList() as $o)
                            <li>@include('partials.icon', ['n' => 'check']) <span>{{ $o }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="margin-top:36px">
                <div class="row-between" style="margin-bottom:14px">
                    <h2 style="font-size:26px;margin:0">محتوى الكورس<span class="dot">.</span></h2>
                    <span class="small muted"><span class="ltr">{{ $course->sections->count() }}</span> فصل · <span class="ltr">{{ $lessonsCount }}</span> درس</span>
                </div>
                <div class="curriculum">
                    @foreach ($course->sections as $i => $section)
                        <details @if ($i === 0) open @endif>
                            <summary>
                                {{ $section->title }}
                                <span class="tiny"><span class="ltr">{{ $section->lessons->count() }}</span> درس</span>
                                <span class="chev">@include('partials.icon', ['n' => 'chev-down', 'class' => 'icon icon-sm'])</span>
                            </summary>
                            @foreach ($section->lessons as $lesson)
                                @php($canOpen = $enrolled || $lesson->is_free_preview)
                                @if ($canOpen)<a href="{{ route('learn.show', [$course, $lesson]) }}" class="lesson-row {{ in_array($lesson->id, $done) ? 'done' : '' }}">@else<div class="lesson-row">@endif
                                    @include('partials.icon', ['n' => in_array($lesson->id, $done) ? 'check-circle' : ($lesson->type === 'video' ? 'play-circle' : 'file'), 'class' => 'icon state'])
                                    <span>{{ $lesson->title }}</span>
                                    <span class="end">
                                        @if ($lesson->is_free_preview && ! $enrolled)<span class="badge">معاينة مجانية</span>@endif
                                        @if (! $canOpen)@include('partials.icon', ['n' => 'lock', 'class' => 'icon icon-sm'])@endif
                                        <span class="ltr">{{ $lesson->durationLabel() }}</span>
                                    </span>
                                @if ($canOpen)</a>@else</div>@endif
                            @endforeach
                        </details>
                    @endforeach
                </div>
            </div>

            @if ($course->description)
                <div style="margin-top:36px">
                    <h2 style="font-size:26px">عن الكورس<span class="dot">.</span></h2>
                    <div class="prose">{{ $course->description }}</div>
                </div>
            @endif
        </div>

        <div>
            @if ($course->instructor)
                <h2 style="font-size:20px">المدرب</h2>
                <div class="instructor">
                    @if ($course->instructor->photoUrl())
                        <img class="ph" src="{{ $course->instructor->photoUrl() }}" alt="">
                    @else
                        <span class="ph">{{ mb_substr($course->instructor->name, 0, 1) }}</span>
                    @endif
                    <div>
                        <strong>{{ $course->instructor->name }}</strong>
                        @if ($course->instructor->title)<div class="small muted">{{ $course->instructor->title }}</div>@endif
                    </div>
                </div>
                @if ($course->instructor->bio)<p class="small muted" style="margin-top:12px">{{ $course->instructor->bio }}</p>@endif
            @endif

            @if ($related->isNotEmpty())
                <h2 style="font-size:20px;margin-top:32px">كورسات من نفس المجال</h2>
                <div class="stack">
                    @foreach ($related as $r)
                        <a href="{{ route('courses.show', $r) }}" class="list-row" style="color:inherit">
                            <span class="cat-icon" style="width:44px;height:44px">@include('partials.icon', ['n' => $course->category->icon])</span>
                            <span><strong style="display:block">{{ $r->title }}</strong><span class="tiny"><span class="ltr">{{ iqd($r->finalPrice(), false) }}</span> د.ع</span></span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
