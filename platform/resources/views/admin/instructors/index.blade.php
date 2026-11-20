@extends('layouts.admin')
@section('heading', 'المدربون')
@section('actions')<a href="{{ route('admin.instructors.create') }}" class="btn btn-primary btn-sm">@include('partials.icon', ['n' => 'plus', 'class' => 'icon icon-sm']) مدرب جديد</a>@endsection

@section('content')
<div class="grid grid-3">
    @forelse ($instructors as $i)
        <div class="panel">
            <div class="instructor" style="border:0;padding:0">
                @if ($i->photoUrl())<img class="ph" src="{{ $i->photoUrl() }}" alt="">@else<span class="ph">{{ mb_substr($i->name, 0, 1) }}</span>@endif
                <div><strong>{{ $i->name }}</strong><div class="tiny">{{ $i->title }}</div><div class="tiny"><span class="ltr">{{ $i->courses_count }}</span> كورس · نسبته <span class="ltr">{{ $i->commission_percent ?? 50 }}%</span> · <a href="{{ route('teach', ['instructor' => $i->id]) }}">الإحصائيات</a></div></div>
            </div>
            <div class="actions" style="margin-top:14px;justify-content:flex-start">
                <a class="btn btn-plain btn-sm" href="{{ route('admin.instructors.edit', $i) }}">تعديل</a>
                <form method="POST" action="{{ route('admin.instructors.destroy', $i) }}" data-confirm="حذف المدرب؟">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
            </div>
        </div>
    @empty
        <div class="empty" style="grid-column:1/-1"><h3>لا يوجد مدربون<span class="dot">.</span></h3></div>
    @endforelse
</div>
@endsection
