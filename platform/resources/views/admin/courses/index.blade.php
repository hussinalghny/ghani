@extends('layouts.admin')
@section('heading', 'الكورسات')
@section('actions')<a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm">@include('partials.icon', ['n' => 'plus', 'class' => 'icon icon-sm']) كورس جديد</a>@endsection

@section('content')
<form class="toolbar" method="GET">
    <div class="search">@include('partials.icon', ['n' => 'search'])<input class="input" name="q" value="{{ request('q') }}" placeholder="ابحث بالعنوان"></div>
</form>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th></th><th>الكورس</th><th>القسم</th><th>السعر</th><th>الدروس</th><th>المشتركين</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        @forelse ($courses as $c)
            <tr>
                <td style="width:80px">@if ($c->coverUrl())<img class="thumb" src="{{ $c->coverUrl() }}" alt="">@else<div class="thumb" style="background:url({{ asset('img/pattern-violet.svg') }}),var(--violet);background-size:120px"></div>@endif</td>
                <td><strong>{{ $c->title }}</strong>@if ($c->is_featured) <span class="badge badge-gold">مميز</span>@endif<div class="tiny">{{ $c->instructor->name ?? 'بدون مدرب' }}</div></td>
                <td>{{ $c->category->name ?? '—' }}</td>
                <td class="ltr">{{ $c->isFree() ? 'مجاني' : iqd($c->finalPrice(), false) }}</td>
                <td class="ltr">{{ $c->lessons_count }}</td>
                <td class="ltr">{{ $c->enrollments_count }}</td>
                <td>@if ($c->is_published)<span class="badge badge-green">منشور</span>@else<span class="badge badge-gray">مسودة</span>@endif</td>
                <td class="actions">
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.courses.curriculum', $c) }}">الدروس</a>
                    <a class="btn btn-plain btn-sm" href="{{ route('admin.courses.edit', $c) }}">تعديل</a>
                    <a class="btn btn-plain btn-sm" href="{{ route('courses.show', $c) }}" target="_blank" aria-label="عرض">@include('partials.icon', ['n' => 'eye', 'class' => 'icon icon-sm'])</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted" style="text-align:center;padding:28px">لا توجد كورسات. ابدأ بإضافة أول كورس.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $courses->links() }}
@endsection
