@extends('layouts.admin')
@section('heading', 'الإشعارات')
@section('sub', 'إشعارات فورية لتطبيق الموبايل عبر Firebase')

@section('content')
<div class="stack" style="max-width:760px">
    <div class="panel">
        <div class="row-between"><h2 style="margin:0">ربط Firebase</h2>
            @if ($configured)<span class="badge badge-green">مربوط · <span class="ltr">{{ $project }}</span></span>@else<span class="badge badge-gray">غير مربوط</span>@endif
        </div>
        <p class="small muted" style="margin:10px 0">من Firebase Console ← Project settings ← Service accounts ← <span class="ltr">Generate new private key</span>، وارفع ملف JSON هنا.</p>
        <form method="POST" action="{{ route('admin.notifications.credentials') }}" enctype="multipart/form-data" class="row">
            @csrf
            <input class="input" type="file" name="file" accept=".json,application/json" required>
            <button class="btn btn-plain" type="submit">رفع</button>
        </form>
        <p class="tiny" style="margin:10px 0 0">الأجهزة المسجلة للإشعارات: <span class="ltr">{{ $devices }}</span></p>
    </div>

    <form class="panel" method="POST" action="{{ route('admin.notifications.send') }}" id="nf">
        @csrf
        <h2>إرسال إشعار</h2>
        <div class="field"><label for="title">العنوان</label><input class="input" id="title" name="title" maxlength="80" required placeholder="جلسة جديدة بانتظارك"></div>
        <div class="field"><label for="body">النص</label><textarea class="input" id="body" name="body" rows="3" maxlength="240" required></textarea></div>
        <div class="field">
            <label>إلى</label>
            <div class="pills">
                <label class="pill"><input type="radio" name="target" value="all" checked> كل المستخدمين</label>
                <label class="pill"><input type="radio" name="target" value="course"> طلاب كورس</label>
            </div>
        </div>
        <div class="field" id="courseField" style="display:none"><label for="course_id">الكورس</label>
            <select class="input" id="course_id" name="course_id"><option value="">اختر</option>@foreach ($courses as $c)<option value="{{ $c->id }}">{{ $c->title }}</option>@endforeach</select>
        </div>
        <button class="btn btn-primary" type="submit" @disabled(! $configured)>إرسال</button>
        <p class="help" style="margin-top:10px">ردود الدعم والمدربين بالمحادثة توصل للطالب كإشعار تلقائيا.</p>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('nf').addEventListener('change', function () {
  document.getElementById('courseField').style.display = this.querySelector('[name=target]:checked').value === 'course' ? '' : 'none';
});
</script>
@endpush
