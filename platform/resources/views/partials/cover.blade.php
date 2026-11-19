@php
    $icons = ['shield', 'globe', 'phone', 'cpu', 'database', 'chart', 'pen', 'book', 'code'];
    $catIcon = $course->category->icon ?? 'code';
@endphp
<div class="cover">
    @if ($course->coverUrl())
        <img src="{{ $course->coverUrl() }}" alt="" loading="lazy">
    @else
        <div class="cover-icon">@include('partials.icon', ['n' => in_array($catIcon, $icons) ? $catIcon : 'code'])</div>
    @endif
    @if (($showTag ?? true) && $course->is_featured)
        <span class="tag">مميز</span>
    @endif
</div>
