@extends('layouts.app')
@section('title', 'كورساتي')

@section('content')
<div class="container">
    <div class="page-head row-between">
        <div>
            <h1>أهلا {{ explode(' ', $user->name)[0] }}<span class="dot">.</span></h1>
            <p>كمل من حيث توقفت.</p>
        </div>
        <a href="{{ route('wallet') }}" class="btn btn-plain hide-sm">@include('partials.icon', ['n' => 'wallet']) رصيدك <span class="ltr">{{ iqd($user->balance, false) }}</span> د.ع</a>
    </div>

    @if ($enrollments->isEmpty())
        <div class="empty">
            <div class="ic">@include('partials.icon', ['n' => 'play-circle'])</div>
            <h3>ما عندك كورسات بعد<span class="dot">.</span></h3>
            <p>اختر كورسك الأول، أو فعل قسيمة كورس من المحفظة.</p>
            <div class="hero-actions">
                <a href="{{ route('courses.index') }}" class="btn btn-primary">تصفح الكورسات</a>
                <a href="{{ route('wallet') }}" class="btn btn-ghost">عندي قسيمة</a>
            </div>
        </div>
    @else
        <div class="grid grid-2">
            @foreach ($enrollments as $e)
                @php($c = $e->course)
                @php($pct = $c->lessons_count ? (int) floor(($doneCounts[$c->id] ?? 0) * 100 / $c->lessons_count) : 0)
                @php($expired = $e->expires_at && $e->expires_at->isPast())
                <a href="{{ $expired ? route('courses.show', $c) : route('learn.show', $c) }}" class="my-course">
                    @include('partials.cover', ['course' => $c, 'showTag' => false])
                    <div class="body">
                        <h3>{{ $c->title }}</h3>
                        <span class="tiny"><span class="ltr">{{ $doneCounts[$c->id] ?? 0 }}/{{ $c->lessons_count }}</span> درس مكتمل
                            @if ($e->expires_at) · {{ $expired ? 'انتهى الاشتراك' : 'ينتهي '.$e->expires_at->format('Y/m/d') }}@endif
                        </span>
                        <div class="progress"><span style="width: {{ $pct }}%"></span></div>
                        <div class="row-between">
                            <span class="small muted ltr">{{ $pct }}%</span>
                            @if ($e->completed_at)<span class="badge badge-green">مكتمل</span>
                            @elseif ($expired)<span class="badge badge-red">منتهي</span>
                            @else<span class="link small">{{ $pct ? 'أكمل' : 'ابدأ' }} @include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</span>@endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
    <div style="height:64px"></div>
</div>
@endsection
