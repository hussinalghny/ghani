@extends('layouts.admin')
@section('heading', 'الأقسام')
@section('actions')<a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">@include('partials.icon', ['n' => 'plus', 'class' => 'icon icon-sm']) قسم جديد</a>@endsection

@section('content')
<div class="table-wrap">
    <table class="table">
        <thead><tr><th></th><th>القسم</th><th>الرابط</th><th>الكورسات</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        @forelse ($categories as $c)
            <tr>
                <td style="width:56px"><span class="cat-icon" style="width:38px;height:38px">@include('partials.icon', ['n' => $c->icon])</span></td>
                <td><strong>{{ $c->name }}</strong><div class="tiny">{{ $c->description }}</div></td>
                <td class="mono small">{{ $c->slug }}</td>
                <td class="ltr">{{ $c->courses_count }}</td>
                <td>@if ($c->is_active)<span class="badge badge-green">ظاهر</span>@else<span class="badge badge-gray">مخفي</span>@endif</td>
                <td class="actions">
                    <a class="btn btn-plain btn-sm" href="{{ route('admin.categories.edit', $c) }}">تعديل</a>
                    <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" data-confirm="حذف القسم؟">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted" style="text-align:center;padding:28px">لا توجد أقسام.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
