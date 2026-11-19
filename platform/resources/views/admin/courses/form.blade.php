@extends('layouts.admin')
@section('heading', $course->exists ? 'تعديل الكورس' : 'كورس جديد')
@section('actions')
    @if ($course->exists)
        <a href="{{ route('admin.courses.curriculum', $course) }}" class="btn btn-primary btn-sm">الفصول والدروس</a>
        <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" data-confirm="حذف الكورس نهائيا؟">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
    @endif
@endsection

@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $course->exists ? route('admin.courses.update', $course) : route('admin.courses.store') }}">
    @csrf @if ($course->exists) @method('PUT') @endif
    <div class="grid" style="grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:16px;align-items:start">
        <div class="panel">
            <h2>المعلومات</h2>
            <div class="form-grid">
                <div class="field full"><label for="title">عنوان الكورس</label><input class="input" id="title" name="title" value="{{ old('title', $course->title) }}" required></div>
                <div class="field full"><label for="subtitle">سطر تعريفي</label><input class="input" id="subtitle" name="subtitle" value="{{ old('subtitle', $course->subtitle) }}" placeholder="تعلم حماية الأنظمة بالذكاء الاصطناعي خلال 4 أسابيع."></div>
                <div class="field"><label for="category_id">القسم</label><select class="input" id="category_id" name="category_id" required>@foreach ($categories as $id => $n)<option value="{{ $id }}" @selected(old('category_id', $course->category_id) == $id)>{{ $n }}</option>@endforeach</select></div>
                <div class="field"><label for="instructor_id">المدرب</label><select class="input" id="instructor_id" name="instructor_id"><option value="">—</option>@foreach ($instructors as $id => $n)<option value="{{ $id }}" @selected(old('instructor_id', $course->instructor_id) == $id)>{{ $n }}</option>@endforeach</select></div>
                <div class="field full"><label for="description">الوصف</label><textarea class="input" id="description" name="description" rows="6">{{ old('description', $course->description) }}</textarea></div>
                <div class="field full"><label for="outcomes">ماذا سيتعلم الطالب (سطر لكل نقطة)</label><textarea class="input" id="outcomes" name="outcomes" rows="5">{{ old('outcomes', $course->outcomes) }}</textarea></div>
                <div class="field"><label for="level">المستوى</label><select class="input" id="level" name="level">@foreach (\App\Models\Course::LEVELS as $k => $v)<option value="{{ $k }}" @selected(old('level', $course->level) === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="field"><label for="duration_label">المدة (نص)</label><input class="input" id="duration_label" name="duration_label" value="{{ old('duration_label', $course->duration_label) }}" placeholder="4 أسابيع · 12 جلسة"></div>
                <div class="field"><label for="slug">الرابط (إنجليزي)</label><input class="input" id="slug" name="slug" dir="ltr" value="{{ old('slug', $course->slug) }}" placeholder="smart-defense"></div>
                <div class="field"><label for="sort">الترتيب</label><input class="input" id="sort" name="sort" type="number" dir="ltr" value="{{ old('sort', $course->sort ?? 0) }}"></div>
            </div>
        </div>
        <div class="stack">
            <div class="panel">
                <h2>السعر والوصول</h2>
                <div class="field"><label for="price">السعر (د.ع)</label><input class="input" id="price" name="price" type="number" min="0" step="250" dir="ltr" value="{{ old('price', $course->price ?? 0) }}" required><span class="help">0 = مجاني</span></div>
                <div class="field"><label for="sale_price">سعر بعد الخصم (اختياري)</label><input class="input" id="sale_price" name="sale_price" type="number" min="0" step="250" dir="ltr" value="{{ old('sale_price', $course->sale_price) }}"></div>
                <div class="field"><label for="access_days">مدة الاشتراك بالأيام</label><input class="input" id="access_days" name="access_days" type="number" min="1" dir="ltr" value="{{ old('access_days', $course->access_days) }}"><span class="help">اتركه فارغا للوصول الدائم.</span></div>
                <div class="stack">
                    <label class="switch"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $course->is_published))> منشور للطلاب</label>
                    <label class="switch"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $course->is_featured))> مميز بالرئيسية</label>
                    <label class="switch"><input type="checkbox" name="has_certificate" value="1" @checked(old('has_certificate', $course->has_certificate))> شهادة إتمام تلقائية</label>
                </div>
            </div>
            <div class="panel">
                <h2>صورة الغلاف</h2>
                @if ($course->coverUrl())<img src="{{ $course->coverUrl() }}" alt="" style="border-radius:12px;margin-bottom:12px;aspect-ratio:16/10;object-fit:cover;width:100%">@endif
                <input class="input" type="file" name="cover" accept="image/*">
                <span class="help">مقاس مقترح 1600×1000. بدون صورة يظهر باترن الأكاديمية.</span>
            </div>
            <button class="btn btn-primary btn-block" type="submit">{{ $course->exists ? 'حفظ التعديلات' : 'إنشاء ومتابعة للدروس' }}</button>
        </div>
    </div>
</form>
@endsection
