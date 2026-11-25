@extends('layouts.admin')
@section('heading', 'الكورسات الحضورية')
@section('sub', 'تسجيل بمقاعد محدودة، والدفع بغني باي يأكد المقعد تلقائيا')

@section('content')
<div class="panel">
    <div class="table-wrap" style="border:0">
        <table class="table">
            <thead><tr><th>الكورس</th><th>المقاعد</th><th>مؤكد</th><th>بانتظار الدفع</th><th>الانتظار</th><th>الإيراد</th><th>التسجيل</th><th></th></tr></thead>
            <tbody>
            @foreach ($cohorts as $c)
                <tr>
                    <td><strong>{{ $c->title }}</strong><div class="tiny muted ltr">/in-person/{{ $c->slug }}</div></td>
                    <td class="ltr">{{ $c->seats }}</td>
                    <td class="ltr"><strong>{{ $c->paid_count }}</strong></td>
                    <td class="ltr">{{ $c->pending_count }}</td>
                    <td class="ltr">{{ $c->waitlist_count }}</td>
                    <td class="ltr">{{ number_format($c->paid_count * $c->price) }}</td>
                    <td><span @class(['badge', 'badge-green' => $c->is_open, 'badge-gray' => ! $c->is_open])>{{ $c->is_open ? 'مفتوح' : 'مغلق' }}</span></td>
                    <td class="row" style="gap:6px"><a class="btn btn-primary btn-sm" href="{{ route('admin.cohorts.show', $c) }}">إدارة</a><a class="btn btn-plain btn-sm" href="{{ route('cohort.show', $c) }}" target="_blank">الصفحة</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
