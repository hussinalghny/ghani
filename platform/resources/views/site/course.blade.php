@extends('layouts.app')
@section('title', $course->title)
@section('description', $course->subtitle)
@section('body_class', 'is-course v4-course')

@section('content')
<div class="container u-course">
    <div class="u-cmain">
        <div class="u-video">
            @if ($course->coverUrl())<img src="{{ $course->coverUrl() }}" alt="">@endif
            @php($preview = $course->sections->flatMap->lessons->firstWhere('is_free_preview', true))
            @if ($preview || $enrolled)
                <a class="u-pl" href="{{ route('learn.show', $enrolled ? [$course] : [$course, $preview]) }}" aria-label="تشغيل"><span>@include('partials.icon', ['n' => 'play'])</span><em>{{ $enrolled ? 'كمل التعلم' : 'شاهد درس المعاينة' }}</em></a>
            @endif
        </div>

        <div class="crumbs"><a href="{{ route('courses.index') }}">الكورسات</a> @include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm']) <a href="{{ route('courses.category', $course->category) }}">{{ $course->category->name }}</a></div>
        <h1 class="u-h1">{{ $course->title }}</h1>
        @if ($course->subtitle)<p class="u-ds">{{ $course->subtitle }}</p>@endif
        <div class="u-meta">
            @include('partials.stars', ['course' => $course])
            @if ($course->rating_count)<span><span class="ltr">{{ $course->rating_count }}</span> تقييم</span>@endif
            @if ($students)<span><span class="ltr">{{ number_format($students) }}</span> طالب</span>@endif
        </div>
        @if ($course->instructor)<div class="u-ins-line">المدرب: <a href="#instructor">{{ $course->instructor->name }}</a></div>@endif
        <div class="u-meta tiny"><span>آخر تحديث {{ ['','كانون الثاني','شباط','آذار','نيسان','أيار','حزيران','تموز','آب','أيلول','تشرين الأول','تشرين الثاني','كانون الأول'][$course->updated_at?->month ?? 0] }} <span class="ltr">{{ $course->updated_at?->year }}</span></span><span>عربي</span></div>

        @if ($course->outcomesList())
            <div class="u-box">
                <h3>شنو راح تتعلم</h3>
                <ul class="u-learn">@foreach ($course->outcomesList() as $o)<li>@include('partials.icon', ['n' => 'check']) <span>{{ $o }}</span></li>@endforeach</ul>
            </div>
        @endif

        <div class="u-box">
            <h3>الكورس يشمل</h3>
            <div class="u-inc">
                @if ($totalDuration)<span>@include('partials.icon', ['n' => 'clock']) {{ duration_human($totalDuration) }} فيديو</span>@endif
                <span>@include('partials.icon', ['n' => 'play-circle']) <span class="ltr">{{ $lessonsCount }}</span> درس</span>
                @if ($course->has_certificate)<span>@include('partials.icon', ['n' => 'award']) شهادة إتمام</span>@endif
                <span>@include('partials.icon', ['n' => 'layers']) موبايل وكمبيوتر</span>
                <span>@include('partials.icon', ['n' => 'chart']) {{ $course->levelLabel() }}</span>
                <span>@include('partials.icon', ['n' => 'check']) وصول {{ $course->access_days ? $course->access_days.' يوم' : 'دائم' }}</span>
            </div>
        </div>

        <div class="u-box" id="content">
            <div class="row-between"><h3 style="margin:0">محتوى الكورس</h3><span class="tiny"><span class="ltr">{{ $course->sections->count() }}</span> أقسام · <span class="ltr">{{ $lessonsCount }}</span> درس</span></div>
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

        @if ($course->instructor)
            @php($ins = $course->instructor)
            <div class="u-box" id="instructor">
                <h3>المدرب</h3>
                <div class="u-ins">
                    @if ($ins->photoUrl())<img src="{{ $ins->photoUrl() }}" alt="">@else<span class="ph">{{ mb_substr($ins->name, 0, 1) }}</span>@endif
                    <div><strong>{{ $ins->name }}</strong>@if ($ins->title)<div class="tiny">{{ $ins->title }}</div>@endif</div>
                </div>
                <div class="u-ins-stats">
                    @php($insCourses = $ins->courses()->where('is_published', true)->count())
                    <span><b class="ltr">{{ $insCourses }}</b> {{ $insCourses == 1 ? 'كورس' : 'كورسات' }}</span>
                    @if ($course->rating_count)<span><b class="ltr">{{ number_format($course->rating_avg, 1) }}</b> تقييم</span>@endif
                    @if ($students)<span><b class="ltr">{{ number_format($students) }}</b> طالب</span>@endif
                </div>
                @if ($ins->bio)<p class="u-bio">{{ $ins->bio }}</p>@endif
            </div>
        @endif

        <div class="u-box" id="reviews">
            <h3>آراء الطلاب</h3>
            @if ($course->rating_count)
                <div class="u-rv-sum">
                    <div class="big"><b class="ltr">{{ number_format($course->rating_avg, 1) }}</b>@include('partials.stars', ['course' => $course])<span class="tiny">تقييم الكورس</span></div>
                    <div class="bars">@foreach ($breakdown as $b)<div><span class="u-bar"><i style="width:{{ $b['percent'] }}%"></i></span><span class="ltr tiny">{{ $b['stars'] }}★ {{ $b['percent'] }}%</span></div>@endforeach</div>
                </div>
                @foreach ($reviews as $r)
                    <div class="u-rv">
                        <div class="row-between"><strong>{{ \Illuminate\Support\Str::of($r->user?->name)->explode(' ')->first() }}</strong><span class="u-stars-s ltr">{{ str_repeat('★', $r->rating) }}<i>{{ str_repeat('★', 5 - $r->rating) }}</i></span></div>
                        @if ($r->body)<p>{{ $r->body }}</p>@endif
                        <span class="tiny">{{ $r->created_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            @else
                <p class="muted small">هذا كورس جديد. أول الطلاب يكتبون آرائهم بعد ما يكملون <span class="ltr">{{ \App\Services\Community::REVIEW_MIN_PROGRESS }}%</span> منه.</p>
            @endif
            @if ($canReview)
                <form method="POST" action="{{ route('courses.review', $course) }}" class="u-rv-form">
                    @csrf
                    <strong>{{ $myReview ? 'عدل تقييمك' : 'قيم الكورس' }}</strong>
                    <div class="u-star-pick">@for ($i = 5; $i >= 1; $i--)<input type="radio" id="st{{ $i }}" name="rating" value="{{ $i }}" @checked(($myReview->rating ?? 5) == $i) required><label for="st{{ $i }}" title="{{ $i }}">★</label>@endfor</div>
                    <label class="sr-only" for="rvb">رأيك</label>
                    <textarea class="input" id="rvb" name="body" rows="3" maxlength="1000" placeholder="شنو عجبك بالكورس؟ (اختياري)">{{ $myReview->body ?? '' }}</textarea>
                    <button class="btn btn-primary btn-sm" type="submit">إرسال التقييم</button>
                </form>
            @elseif ($enrolled)
                <p class="tiny" style="margin-top:12px">تقدر تقيم الكورس بعد ما تكمل <span class="ltr">{{ \App\Services\Community::REVIEW_MIN_PROGRESS }}%</span> منه.</p>
            @endif
        </div>

        @if ($course->description)
            <div class="u-box"><h3>عن الكورس</h3><div class="prose">{{ $course->description }}</div></div>
        @endif
    </div>

    <aside class="u-side">
        <div class="buy-card u-buy" id="buy">
            <div class="buy-body">
                @if ($enrolled)
                    <div class="row-between" style="margin-bottom:10px"><strong>تقدمك</strong><span class="ltr">{{ $progress }}%</span></div>
                    <div class="progress" style="margin-bottom:18px"><span style="width: {{ $progress }}%"></span></div>
                    <a href="{{ route('learn.show', $course) }}" class="btn btn-primary btn-block">{{ $progress ? 'أكمل من حيث توقفت' : 'ابدأ الدرس الأول' }}</a>
                    @if ($course->instructor?->user_id)
                        <form method="POST" action="{{ route('chat.instructor', $course) }}" style="margin-top:8px">@csrf<button class="btn btn-plain btn-block" type="submit">@include('partials.icon', ['n' => 'mic', 'class' => 'icon icon-sm']) اسأل المدرب</button></form>
                    @endif
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
                                <button class="btn btn-primary btn-block" type="submit">{{ $course->isFree() ? 'سجل مجانا' : 'سجل الآن' }}</button>
                            </form>
                        @else
                            @if (\App\Services\PaymentService::enabled())
                                @include('partials.pay-methods', ['course' => $course])
                                <p class="tiny" style="margin:10px 0 0;text-align:center">تحويل مباشر، والكورس ينفتح تلقائيا أول ما يوصل. أو</p>
                                <a href="{{ route('wallet') }}" class="btn btn-plain btn-block" style="margin-top:6px">عندي قسيمة</a>
                            @else
                                <a href="{{ route('wallet') }}" class="btn btn-primary btn-block">اشحن رصيدك للاشتراك</a>
                            @endif
                            <p class="tiny" style="margin:10px 0 0;text-align:center">تحتاج <span class="ltr">{{ iqd($course->finalPrice() - auth()->user()->balance, false) }}</span> د.ع إضافية. عندك قسيمة؟ اكتبها بالمحفظة.</p>
                        @endif
                    @else
                        <p class="small muted" style="margin:6px 0 16px">سجل حسابك واشحن رصيدك بقسيمة للاشتراك.</p>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-block">احجز مقعدك</a>
                        <a href="{{ route('login') }}" class="btn btn-plain btn-block" style="margin-top:8px">عندي حساب</a>
                    @endauth
                @endif
                <ul class="u-checks">
                    <li>@include('partials.icon', ['n' => 'check']) وصول {{ $course->access_days ? 'لمدة '.$course->access_days.' يوم' : 'دائم' }}</li>
                    <li>@include('partials.icon', ['n' => 'check']) مشاهدة من الموبايل والكمبيوتر</li>
                    @if ($course->has_certificate)<li>@include('partials.icon', ['n' => 'check']) شهادة إتمام قابلة للتحقق</li>@endif
                </ul>
            </div>
        </div>
    </aside>
</div>

@unless ($enrolled)
<div class="ga-buybar">
    <div><b>@if ($course->isFree())مجاني@else<span class="ltr">{{ iqd($course->finalPrice(), false) }}</span> د.ع@endif</b><small>وصول كامل + شهادة</small></div>
    <a class="btn btn-primary" href="#buy">سجل الآن</a>
</div>
@endunless
@endsection
