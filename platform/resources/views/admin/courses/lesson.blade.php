@extends('layouts.admin')
@section('heading', $lesson->exists ? 'تعديل الدرس' : 'درس جديد')
@section('sub', $course->title)

@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $lesson->exists ? route('admin.lessons.update', $lesson) : route('admin.lessons.store', $course) }}">
    @csrf @if ($lesson->exists) @method('PUT') @endif
    <div class="grid" style="grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:16px;align-items:start">
        <div class="stack">
            <div class="panel">
                <div class="form-grid">
                    <div class="field full"><label for="title">عنوان الدرس</label><input class="input" id="title" name="title" value="{{ old('title', $lesson->title) }}" required></div>
                    <div class="field"><label for="section_id">الفصل</label><select class="input" id="section_id" name="section_id">@foreach ($course->sections as $s)<option value="{{ $s->id }}" @selected(old('section_id', $lesson->section_id) == $s->id)>{{ $s->title }}</option>@endforeach</select></div>
                    <div class="field"><label for="type">النوع</label><select class="input" id="type" name="type">@foreach (\App\Models\Lesson::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $lesson->type) === $k)>{{ $v }}</option>@endforeach</select></div>
                </div>
            </div>

            <div class="panel" id="videoPanel">
                <h2>الفيديو</h2>
                <input type="hidden" name="video_path" id="video_path" value="{{ old('video_path') }}">
                @if ($lesson->video_path)
                    <div class="list-row" style="padding-top:0">@include('partials.icon', ['n' => 'play-circle']) <span style="flex:1">فيديو مرفوع على السيرفر</span>
                        <label class="switch"><input type="checkbox" name="remove_video" value="1"> حذف</label></div>
                @endif
                <div class="dropzone" id="drop">
                    @include('partials.icon', ['n' => 'upload'])
                    <p style="margin:8px 0 0"><strong>{{ $lesson->video_path ? 'بدّل الفيديو: اسحبه هنا أو اضغط' : 'اسحب الفيديو هنا أو اضغط للاختيار' }}</strong></p>
                    <p class="tiny" style="margin:0">MP4 (H.264) أفضل صيغة. يترفع على أجزاء فما يتأثر بحد الرفع.</p>
                    <input type="file" id="videoFile" accept="video/mp4,video/webm,video/quicktime,.m4v" hidden>
                </div>
                <div class="upbar" id="upbar"><span></span></div>
                <p class="tiny" id="upmsg" style="margin:8px 0 0"></p>
                <div class="field" style="margin-top:14px"><label for="duration_min">المدة بالدقائق</label><input class="input" id="duration_min" name="duration_min" type="number" step="0.1" min="0" dir="ltr" value="{{ old('duration_min', $lesson->duration ? round($lesson->duration / 60, 1) : '') }}"><span class="help">تنحسب تلقائيا عند اختيار الفيديو.</span></div>
                <details style="margin-top:6px"><summary class="tiny" style="cursor:pointer">Bunny Stream (اختياري، مدفوع)</summary>
                    <div class="field" style="margin-top:10px"><label for="bunny_video_id">رقم فيديو Bunny</label><input class="input mono" id="bunny_video_id" name="bunny_video_id" dir="ltr" value="{{ old('bunny_video_id', $lesson->bunny_video_id) }}"></div>
                </details>
            </div>

            <div class="panel">
                <h2>المحتوى النصي (اختياري)</h2>
                <textarea class="input" name="body" rows="8" aria-label="المحتوى">{{ old('body', $lesson->body) }}</textarea>
            </div>
        </div>

        <div class="stack">
            <div class="panel">
                <h2>ملف مرفق</h2>
                @if ($lesson->attachment)
                    <div class="list-row" style="padding-top:0">@include('partials.icon', ['n' => 'file']) <span style="flex:1">{{ $lesson->attachment_name }}</span></div>
                    <label class="switch" style="margin-bottom:12px"><input type="checkbox" name="remove_attachment" value="1"> حذف الملف</label>
                @endif
                <input class="input" type="file" name="attachment">
                <span class="help">PDF أو ZIP حتى <span class="ltr">50MB</span>. ينحفظ بمجلد محمي ويتحمل برابط مؤقت.</span>
            </div>
            <div class="panel">
                <label class="switch"><input type="checkbox" name="is_free_preview" value="1" @checked(old('is_free_preview', $lesson->is_free_preview))> معاينة مجانية</label>
                <p class="help" style="margin:8px 0 0">يقدر أي زائر يشوف هذا الدرس قبل الاشتراك.</p>
            </div>
            <button class="btn btn-primary btn-block" type="submit" id="saveBtn">حفظ الدرس</button>
            <a class="btn btn-plain btn-block" href="{{ route('admin.courses.curriculum', $course) }}">رجوع</a>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var type = document.getElementById('type'), panel = document.getElementById('videoPanel');
  var sync = function () { panel.style.display = type.value === 'video' ? '' : 'none'; };
  type.addEventListener('change', sync); sync();

  var drop = document.getElementById('drop'), input = document.getElementById('videoFile');
  var bar = document.getElementById('upbar'), msg = document.getElementById('upmsg'), save = document.getElementById('saveBtn');
  var csrf = document.querySelector('meta[name=csrf-token]').content;
  var CHUNK = 5 * 1024 * 1024;
  drop.addEventListener('click', function () { input.click(); });
  drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.classList.add('over'); });
  drop.addEventListener('dragleave', function () { drop.classList.remove('over'); });
  drop.addEventListener('drop', function (e) { e.preventDefault(); drop.classList.remove('over'); if (e.dataTransfer.files[0]) start(e.dataTransfer.files[0]); });
  input.addEventListener('change', function () { if (input.files[0]) start(input.files[0]); });

  function start(file) {
    // المدة من الملف نفسه قبل الرفع
    var v = document.createElement('video'); v.preload = 'metadata';
    v.onloadedmetadata = function () { document.getElementById('duration_min').value = (v.duration / 60).toFixed(1); URL.revokeObjectURL(v.src); };
    v.src = URL.createObjectURL(file);

    var id = (Date.now().toString(36) + Math.random().toString(36).slice(2)).replace(/[^a-z0-9]/g, '').slice(0, 30);
    var total = Math.max(1, Math.ceil(file.size / CHUNK)), i = 0, tries = 0;
    save.disabled = true; bar.style.display = 'block';
    var next = function () {
      var fd = new FormData();
      fd.append('upload_id', id); fd.append('index', i); fd.append('total', total); fd.append('name', file.name);
      fd.append('chunk', file.slice(i * CHUNK, (i + 1) * CHUNK), 'chunk');
      fetch('{{ route('admin.videos.chunk') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.message || 'خطأ'); return j; }); })
        .then(function (j) {
          tries = 0; i++;
          var p = Math.round(i / total * 100);
          bar.firstElementChild.style.width = p + '%'; msg.textContent = 'جاري الرفع ' + p + '%';
          if (j.path) { document.getElementById('video_path').value = j.path; msg.textContent = 'تم رفع الفيديو (' + j.size + ' MB). احفظ الدرس.'; save.disabled = false; }
          else next();
        })
        .catch(function (e) {
          if (++tries <= 3) { msg.textContent = 'انقطع الاتصال، نعيد المحاولة...'; setTimeout(next, 2000 * tries); }
          else { msg.textContent = 'فشل الرفع: ' + e.message; save.disabled = false; }
        });
    };
    next();
  }
})();
</script>
@endpush
