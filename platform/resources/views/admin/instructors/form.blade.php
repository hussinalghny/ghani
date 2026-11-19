@extends('layouts.admin')
@section('heading', $instructor->exists ? 'تعديل المدرب' : 'مدرب جديد')

@section('content')
<form class="panel" style="max-width:720px" method="POST" enctype="multipart/form-data" action="{{ $instructor->exists ? route('admin.instructors.update', $instructor) : route('admin.instructors.store') }}">
    @csrf @if ($instructor->exists) @method('PUT') @endif
    <div class="form-grid">
        <div class="field"><label for="name">الاسم</label><input class="input" id="name" name="name" value="{{ old('name', $instructor->name) }}" required></div>
        <div class="field"><label for="title">المسمى</label><input class="input" id="title" name="title" value="{{ old('title', $instructor->title) }}" placeholder="مهندس أمن شبكات"></div>
        <div class="field full"><label for="bio">نبذة</label><textarea class="input" id="bio" name="bio">{{ old('bio', $instructor->bio) }}</textarea></div>
        <div class="field full"><label for="photo">الصورة</label><input class="input" id="photo" name="photo" type="file" accept="image/*"></div>
    </div>
    <div class="row"><button class="btn btn-primary" type="submit">حفظ</button><a class="btn btn-plain" href="{{ route('admin.instructors.index') }}">إلغاء</a></div>
</form>
@endsection
