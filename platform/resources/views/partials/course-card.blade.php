<a href="{{ route('courses.show', $course) }}" class="course-card">
    @include('partials.cover', ['course' => $course])
    <div class="course-body">
        <span class="course-cat">{{ $course->category->name ?? '' }}</span>
        <h3>{{ $course->title }}<span class="dot">.</span></h3>
        @if ($course->subtitle)<p>{{ $course->subtitle }}</p>@endif
        <div class="course-meta">
            <span>@include('partials.icon', ['n' => 'play-circle', 'class' => 'icon icon-sm']) <span class="ltr">{{ $course->lessons_count ?? $course->lessons()->count() }}</span> درس</span>
            @if ($course->duration_label)<span>@include('partials.icon', ['n' => 'clock', 'class' => 'icon icon-sm']) {{ $course->duration_label }}</span>@endif
            <span>{{ $course->levelLabel() }}</span>
        </div>
        <div class="course-foot">
            @if ($course->isFree())
                <span class="price free">مجاني</span>
            @else
                <span class="price"><span class="ltr">{{ iqd($course->finalPrice(), false) }}</span> د.ع @if ($course->finalPrice() < $course->price)<del class="ltr">{{ iqd($course->price, false) }}</del>@endif</span>
            @endif
            <span class="link">التفاصيل @include('partials.icon', ['n' => 'chev-left', 'class' => 'icon icon-sm'])</span>
        </div>
    </div>
</a>
