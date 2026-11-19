@extends('layouts.admin')
@section('heading', $category->exists ? 'تعديل القسم' : 'قسم جديد')

@section('content')
<form class="panel" style="max-width:720px" method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
    @csrf @if ($category->exists) @method('PUT') @endif
    <div class="form-grid">
        <div class="field"><label for="name">الاسم</label><input class="input" id="name" name="name" value="{{ old('name', $category->name) }}" required></div>
        <div class="field"><label for="slug">الرابط (إنجليزي)</label><input class="input" id="slug" name="slug" dir="ltr" value="{{ old('slug', $category->slug) }}" placeholder="web-development"></div>
        <div class="field full"><label for="description">وصف قصير</label><input class="input" id="description" name="description" value="{{ old('description', $category->description) }}"></div>
        <div class="field"><label for="icon">الأيقونة</label>
            <select class="input" id="icon" name="icon">@foreach ($icons as $k => $v)<option value="{{ $k }}" @selected(old('icon', $category->icon) === $k)>{{ $v }}</option>@endforeach</select>
        </div>
        <div class="field"><label for="sort">الترتيب</label><input class="input" id="sort" name="sort" type="number" dir="ltr" value="{{ old('sort', $category->sort ?? 0) }}"></div>
    </div>
    <label class="switch" style="margin:6px 0 20px"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> ظاهر بالمنصة</label>
    <div class="row"><button class="btn btn-primary" type="submit">حفظ</button><a class="btn btn-plain" href="{{ route('admin.categories.index') }}">إلغاء</a></div>
</form>
@endsection
