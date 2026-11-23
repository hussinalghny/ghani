{{-- بطاقة كورس بأسلوب المنصات التعليمية: صورة، عنوان، مدرب، تقييم، سعر --}}
<a href="{{ route('courses.show', $course) }}" class="u-card">
    <div class="u-im">
        @if ($course->coverUrl())<img src="{{ $course->thumbUrl() }}" alt="" loading="lazy" decoding="async">@else<div class="u-ph">@include('partials.icon', ['n' => $course->category->icon ?? 'code'])</div>@endif
    </div>
    <h4>{{ $course->title }}</h4>
    <div class="u-by">{{ $course->instructor?->name ?? $course->category?->name }}</div>
    @include('partials.stars', ['course' => $course])
    <div class="u-price">@if ($course->isFree())مجاني@else<span class="ltr">{{ iqd($course->finalPrice(), false) }}</span> د.ع @if ($course->finalPrice() < $course->price)<del class="ltr">{{ iqd($course->price, false) }}</del>@endif @endif</div>
    @if ($course->is_featured)<span class="u-badge">الأكثر طلبا</span>@endif
</a>
