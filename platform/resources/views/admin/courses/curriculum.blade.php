@extends('layouts.admin')
@section('heading', $course->title)
@section('sub', 'الفصول والدروس · اسحب لتغيير الترتيب')
@section('actions')
    <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-plain btn-sm">معلومات الكورس</a>
    <a href="{{ route('courses.show', $course) }}" target="_blank" class="btn btn-plain btn-sm">@include('partials.icon', ['n' => 'eye', 'class' => 'icon icon-sm']) عرض</a>
@endsection

@section('content')
<div id="sections" data-reorder="{{ route('admin.curriculum.reorder', $course) }}">
    @forelse ($course->sections as $section)
        <div class="cur-section" draggable="true" data-section="{{ $section->id }}">
            <header>
                <span class="handle" title="اسحب">@include('partials.icon', ['n' => 'drag'])</span>
                <form method="POST" action="{{ route('admin.sections.update', $section) }}" style="flex:1;display:flex">@csrf @method('PUT')
                    <input name="title" value="{{ $section->title }}" aria-label="اسم الفصل" onchange="this.form.submit()">
                </form>
                <span class="tiny"><span class="ltr">{{ $section->lessons->count() }}</span> درس</span>
                <a class="btn btn-primary btn-sm" href="{{ route('admin.lessons.create', [$course, 'section' => $section->id]) }}">@include('partials.icon', ['n' => 'plus', 'class' => 'icon icon-sm']) درس</a>
                <form method="POST" action="{{ route('admin.sections.destroy', $section) }}" data-confirm="حذف الفصل وكل دروسه؟">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit" aria-label="حذف الفصل">@include('partials.icon', ['n' => 'trash', 'class' => 'icon icon-sm'])</button></form>
            </header>
            <div class="lessons" data-lessons="{{ $section->id }}">
                @foreach ($section->lessons as $lesson)
                    <div class="cur-lesson" draggable="true" data-lesson="{{ $lesson->id }}">
                        <span class="handle">@include('partials.icon', ['n' => 'drag'])</span>
                        @include('partials.icon', ['n' => $lesson->type === 'video' ? 'play-circle' : 'file'])
                        <span style="flex:1">{{ $lesson->title }}
                            @if ($lesson->is_free_preview)<span class="badge">معاينة</span>@endif
                            @if ($lesson->type === 'video' && ! $lesson->bunny_video_id && ! $lesson->video_path)<span class="badge badge-red">بدون فيديو</span>@endif
                            @if ($lesson->attachment)<span class="badge badge-gray">ملف</span>@endif
                        </span>
                        <span class="tiny ltr">{{ $lesson->durationLabel() }}</span>
                        <a class="btn btn-plain btn-sm" href="{{ route('admin.lessons.edit', $lesson) }}">تعديل</a>
                        <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}" data-confirm="حذف الدرس؟">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit" aria-label="حذف">@include('partials.icon', ['n' => 'trash', 'class' => 'icon icon-sm'])</button></form>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty" style="margin-bottom:16px"><div class="ic">@include('partials.icon', ['n' => 'layers'])</div><h3>ابدأ بأول فصل<span class="dot">.</span></h3><p>مثلا: الأسبوع الأول، الأساسيات.</p></div>
    @endforelse
</div>

<form class="panel row" method="POST" action="{{ route('admin.sections.store', $course) }}">
    @csrf
    <input class="input" name="title" placeholder="اسم فصل جديد" required aria-label="اسم فصل جديد">
    <button class="btn btn-primary" type="submit">إضافة فصل</button>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var root = document.getElementById('sections'); if (!root) return;
  var dragged = null;
  root.addEventListener('dragstart', function (e) {
    dragged = e.target.closest('.cur-lesson') || e.target.closest('.cur-section');
    if (dragged) { dragged.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
  });
  root.addEventListener('dragend', function () { if (dragged) dragged.classList.remove('dragging'); dragged = null; save(); });
  root.addEventListener('dragover', function (e) {
    if (!dragged) return; e.preventDefault();
    if (dragged.classList.contains('cur-lesson')) {
      var list = e.target.closest('[data-lessons]'); if (!list) return;
      var after = [].slice.call(list.querySelectorAll('.cur-lesson:not(.dragging)')).find(function (el) {
        var r = el.getBoundingClientRect(); return e.clientY < r.top + r.height / 2;
      });
      after ? list.insertBefore(dragged, after) : list.appendChild(dragged);
    } else {
      var sec = e.target.closest('.cur-section'); if (!sec || sec === dragged) return;
      var r = sec.getBoundingClientRect();
      e.clientY < r.top + r.height / 2 ? root.insertBefore(dragged, sec) : root.insertBefore(dragged, sec.nextSibling);
    }
  });
  function save() {
    var body = { sections: [], lessons: {} };
    root.querySelectorAll('.cur-section').forEach(function (s) {
      body.sections.push(s.dataset.section);
      body.lessons[s.dataset.section] = [].map.call(s.querySelectorAll('.cur-lesson'), function (l) { return l.dataset.lesson; });
    });
    fetch(root.dataset.reorder, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(body) });
  }
})();
</script>
@endpush
