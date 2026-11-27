@extends('layouts.admin')
@section('heading', request('role') === 'reseller' ? 'الموزعون' : 'الطلاب')
@unless (auth()->user()->isSupport())
@section('actions')<a href="{{ route('admin.students.create') }}" class="btn btn-primary btn-sm">@include('partials.icon', ['n' => 'plus', 'class' => 'icon icon-sm']) حساب جديد</a>@endsection
@endunless

@section('content')
<form class="toolbar" method="GET">
    <div class="search">@include('partials.icon', ['n' => 'search'])<input class="input" name="q" value="{{ request('q') }}" placeholder="الاسم أو رقم الهاتف"></div>
    @unless (auth()->user()->isSupport())
    <select class="input" name="role" style="max-width:180px" onchange="this.form.submit()">
        <option value="">كل الحسابات</option>
        @foreach ($roles as $k => $v)<option value="{{ $k }}" @selected(request('role') === $k)>{{ $v }}</option>@endforeach
    </select>
    @endunless
</form>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>الاسم</th><th>الهاتف</th><th>النوع</th><th>الرصيد</th><th>الكورسات</th><th>الأجهزة</th><th>التسجيل</th><th></th></tr></thead>
        <tbody>
        @forelse ($users as $u)
            <tr>
                <td><strong>{{ $u->name }}</strong> @unless ($u->is_active)<span class="badge badge-red">موقوف</span>@endunless</td>
                <td class="ltr">{{ $u->phone }}</td>
                <td><span class="badge {{ $u->role === 'admin' ? 'badge-gold' : '' }}">{{ $roles[$u->role] ?? $u->role }}</span></td>
                <td class="ltr">{{ iqd($u->balance, false) }}</td>
                <td class="ltr">{{ $u->enrollments_count }}</td>
                <td class="ltr">{{ $u->devices_count }}</td>
                <td class="ltr">{{ $u->created_at->format('Y/m/d') }}</td>
                <td><a class="btn btn-plain btn-sm" href="{{ route('admin.students.show', $u) }}">فتح</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted" style="text-align:center;padding:28px">لا توجد نتائج.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
