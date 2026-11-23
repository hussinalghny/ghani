@if (($course->rating_count ?? 0) > 0)
    <div class="u-rate"><b class="ltr">{{ number_format($course->rating_avg, 1) }}</b><span class="st" aria-label="{{ $course->rating_avg }} من 5">@for ($i = 1; $i <= 5; $i++)<i @class(['on' => $i <= round($course->rating_avg)])>★</i>@endfor</span><span class="ltr">({{ $course->rating_count }})</span></div>
@else
    <div class="u-rate"><span class="u-new">كورس جديد</span></div>
@endif
