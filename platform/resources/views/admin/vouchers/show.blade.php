@extends('layouts.admin')
@section('heading', $batch->name)
@section('sub')
    {{ $batch->course ? 'قسيمة كورس: '.$batch->course->title : 'قسيمة رصيد' }} · <span class="ltr">{{ iqd($batch->amount, false) }}</span> د.ع × <span class="ltr">{{ $batch->quantity }}</span>
    @if ($batch->reseller) · الموزع: {{ $batch->reseller->name }}@endif
@endsection
@section('actions')
    <a class="btn btn-plain btn-sm" href="{{ route('admin.vouchers.csv', $batch) }}">@include('partials.icon', ['n' => 'download', 'class' => 'icon icon-sm']) Excel</a>
    <a class="btn btn-primary btn-sm" href="{{ route('admin.vouchers.print', $batch) }}" target="_blank">@include('partials.icon', ['n' => 'printer', 'class' => 'icon icon-sm']) طباعة الكروت</a>
    <form method="POST" action="{{ route('admin.vouchers.disableBatch', $batch) }}" data-confirm="تعطيل كل القسائم غير المستخدمة بهذه الدفعة؟">@csrf<button class="btn btn-danger btn-sm" type="submit">تعطيل الدفعة</button></form>
@endsection

@section('content')
<div class="pills" style="margin-bottom:16px">
    <a href="{{ route('admin.vouchers.show', $batch) }}" @class(['pill', 'active' => ! request('status')])>الكل</a>
    @foreach (\App\Models\Voucher::STATUSES as $k => $v)
        <a href="{{ route('admin.vouchers.show', [$batch, 'status' => $k]) }}" @class(['pill', 'active' => request('status') === $k])>{{ $v }}</a>
    @endforeach
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>الكود</th><th>الحالة</th><th>بيعت</th><th>استخدمها</th><th>وقت الاستخدام</th><th></th></tr></thead>
        <tbody>
        @foreach ($vouchers as $v)
            <tr>
                <td class="mono">{{ $v->formatted() }}</td>
                <td><span @class(['badge', 'badge-green' => $v->status === 'active', 'badge-gray' => $v->status === 'redeemed', 'badge-red' => $v->status === 'disabled'])>{{ $v->statusLabel() }}</span></td>
                <td class="ltr">{{ $v->sold_at?->format('Y/m/d') ?? '—' }}</td>
                <td>@if ($v->redeemer)<a href="{{ route('admin.students.show', $v->redeemer) }}">{{ $v->redeemer->name }}</a> <span class="tiny ltr">{{ $v->redeemer->phone }}</span>@else — @endif</td>
                <td class="ltr">{{ $v->redeemed_at?->format('Y/m/d H:i') ?? '—' }}</td>
                <td>@if ($v->status !== 'redeemed')<form method="POST" action="{{ route('admin.vouchers.toggle', $v) }}">@csrf<button class="btn btn-plain btn-sm" type="submit">{{ $v->status === 'active' ? 'تعطيل' : 'تفعيل' }}</button></form>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $vouchers->links() }}
@endsection
