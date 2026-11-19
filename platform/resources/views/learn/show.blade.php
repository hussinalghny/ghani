@extends('layouts.app')
@section('title', $lesson->title)
@section('no_footer', true)

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="learn">
    <div class="learn-main">
        @if ($lesson->type === 'video')
            <div class="player-shell" id="player" data-progress-url="{{ route('learn.progress', $lesson) }}" data-enrolled="{{ $enrolled ? 1 : 0 }}" data-start="{{ (int) optional($done->get($lesson->id))->position }}">
                @if ($playback['embed_url'])
                    {{-- لا نضع allowfullscreen: ملء الشاشة يصير على الغلاف حتى تبقى العلامة المائية ظاهرة --}}
                    <iframe id="bunny" src="{{ $playback['embed_url'] }}" loading="eager" referrerpolicy="origin" allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture 'none'" title="{{ $lesson->title }}"></iframe>
                @else
                    <div class="player-empty"><div><strong>الفيديو قيد التجهيز</strong><p class="small" style="margin:6px 0 0">ارجع بعد قليل.</p></div></div>
                @endif
                @if ($playback['watermark'])
                    <div class="watermark" id="wm">{{ $playback['watermark'] }}</div>
                @endif
                <div class="player-shield"><div><strong>المحتوى محمي</strong><p class="small" style="margin:6px 0 0;color:var(--soft)">التصوير والتسجيل غير مسموح. الفيديو معلم برقم حسابك.</p></div></div>
            </div>
        @endif

        <div class="lesson-head">
            <div>
                <div class="crumbs" style="margin:0 0 6px"><a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a></div>
                <h1>{{ $lesson->title }}</h1>
            </div>
            <div class="row">
                @if ($lesson->type === 'video' && $playback['embed_url'])
                    <button class="btn btn-plain btn-sm" type="button" id="fs">ملء الشاشة</button>
                @endif
                @if ($enrolled)
                    @php($isDone = (bool) optional($done->get($lesson->id))->completed_at)
                    <button class="btn {{ $isDone ? 'btn-plain' : 'btn-primary' }} btn-sm" type="button" id="markDone" @disabled($isDone)>
                        @include('partials.icon', ['n' => 'check', 'class' => 'icon icon-sm']) <span>{{ $isDone ? 'مكتمل' : 'أكملت الدرس' }}</span>
                    </button>
                @endif
            </div>
        </div>

        @if (! $enrolled)
            <div class="alert" style="margin-top:12px">@include('partials.icon', ['n' => 'info']) <span>هذا درس معاينة مجاني. <a href="{{ route('courses.show', $course) }}">اشترك بالكورس</a> حتى تفتح كل الدروس.</span></div>
        @endif

        @if ($lesson->body)
            <div class="lesson-body no-select">{{ $lesson->body }}</div>
        @endif

        @if ($fileUrl)
            <a class="attachment" href="{{ $fileUrl }}">
                <span class="ic">@include('partials.icon', ['n' => 'file'])</span>
                <span><strong style="display:block">{{ $lesson->attachment_name ?: 'ملف الدرس' }}</strong><span class="tiny">الرابط صالح لمدة 10 دقائق</span></span>
                <span style="margin-inline-start:auto;color:var(--violet)">@include('partials.icon', ['n' => 'download'])</span>
            </a>
        @endif

        <div class="lesson-nav">
            @if ($prev)
                <a class="btn btn-plain" href="{{ route('learn.show', [$course, $prev]) }}">@include('partials.icon', ['n' => 'chev-right', 'class' => 'icon icon-sm']) السابق</a>
            @endif
            @if ($next)
                <a class="btn btn-primary" href="{{ route('learn.show', [$course, $next]) }}">التالي @include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</a>
            @endif
        </div>
    </div>

    <aside class="learn-side" aria-label="دروس الكورس">
        <div class="side-head">
            <h2>{{ $course->title }}</h2>
            @if ($enrolled)
                <div class="row-between tiny" style="margin-bottom:6px"><span>التقدم</span><span class="ltr" id="pct">{{ $progress }}%</span></div>
                <div class="progress"><span id="bar" style="width: {{ $progress }}%"></span></div>
            @endif
        </div>
        @foreach ($course->sections as $section)
            @php($sectionLessons = $lessons->where('section_id', $section->id))
            @continue($sectionLessons->isEmpty())
            <div class="side-section">{{ $section->title }}</div>
            @foreach ($sectionLessons as $l)
                @php($isDone = (bool) optional($done->get($l->id))->completed_at)
                @php($open = $enrolled || $l->is_free_preview)
                <a href="{{ $open ? route('learn.show', [$course, $l]) : route('courses.show', $course) }}"
                   @class(['side-lesson', 'current' => $l->id === $lesson->id, 'done' => $isDone, 'locked' => ! $open])>
                    <span class="state">
                        @if ($isDone)@include('partials.icon', ['n' => 'check', 'class' => 'icon icon-sm'])
                        @elseif (! $open)@include('partials.icon', ['n' => 'lock', 'class' => 'icon icon-sm'])@endif
                    </span>
                    <span>{{ $l->title }}<span class="d ltr">{{ $l->durationLabel() }}</span></span>
                </a>
            @endforeach
        @endforeach
    </aside>
</div>
@endsection

@push('scripts')
<script src="https://assets.mediadelivery.net/playerjs/player-0.1.0.min.js"></script>
<script src="{{ asset('js/player-guard.js') }}?v=1"></script>
@endpush
