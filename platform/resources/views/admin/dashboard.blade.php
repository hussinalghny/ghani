@extends('layouts.admin')
@section('heading', 'نظرة عامة')
@section('sub', 'أرقام هذا الشهر')
@section('actions')
    <a href="{{ route('admin.vouchers.create') }}" class="btn btn-gold btn-sm">@include('partials.icon', ['n' => 'ticket', 'class' => 'icon icon-sm']) إصدار قسائم</a>
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm">@include('partials.icon', ['n' => 'plus', 'class' => 'icon icon-sm']) كورس جديد</a>
@endsection

@section('content')
<div class="kpis">
    <div class="kpi"><span>الطلاب</span><strong class="ltr">{{ number_format($stats['students']) }}</strong><em>+<span class="ltr">{{ $stats['new_students'] }}</span> هذا الأسبوع</em></div>
    <div class="kpi"><span>مبيعات الكورسات (الشهر)</span><strong class="ltr">{{ iqd($stats['sales_month'], false) }}</strong><em style="color:var(--text-3)">دينار عراقي</em></div>
    <div class="kpi"><span>قسائم مشحونة (الشهر)</span><strong class="ltr">{{ iqd($stats['vouchers_redeemed_month'], false) }}</strong><em style="color:var(--text-3)"><span class="ltr">{{ number_format($stats['vouchers_active']) }}</span> قسيمة فعالة</em></div>
    <div class="kpi"><span>أرصدة الطلاب</span><strong class="ltr">{{ iqd($stats['balances'], false) }}</strong><em style="color:var(--text-3)"><span class="ltr">{{ $stats['enrollments'] }}</span> اشتراك كلي</em></div>
</div>

<div class="grid" style="grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:16px">
    <div class="panel">
        <h2>الاشتراكات آخر 14 يوم</h2>
        @php($max = max(1, $chart->max('c')))
        <div class="chart">
            @foreach ($chart as $i => $p)
                <div @class(['b', 'today' => $loop->last]) style="height: {{ max(3, $p['c'] / $max * 100) }}%" title="{{ $p['d'] }}"><span class="ltr">{{ $p['c'] ?: '' }}</span></div>
            @endforeach
        </div>
    </div>
    <div class="panel">
        <h2>الأكثر اشتراكا</h2>
        @forelse ($topCourses as $c)
            <div class="list-row"><span style="flex:1">{{ $c->title }}</span><span class="badge ltr">{{ $c->enrollments_count }}</span></div>
        @empty
            <p class="muted small">لا توجد كورسات.</p>
        @endforelse
    </div>
</div>

<div class="panel" style="margin-top:16px">
    <h2>آخر الاشتراكات</h2>
    <div class="table-wrap" style="border:0">
        <table class="table">
            <thead><tr><th>الطالب</th><th>الكورس</th><th>الطريقة</th><th>المبلغ</th><th>الوقت</th></tr></thead>
            <tbody>
            @forelse ($recent as $e)
                <tr>
                    <td><a href="{{ route('admin.students.show', $e->user_id) }}">{{ $e->user->name ?? '—' }}</a></td>
                    <td>{{ $e->course->title ?? '—' }}</td>
                    <td><span class="badge">{{ \App\Models\Enrollment::SOURCES[$e->source] ?? $e->source }}</span></td>
                    <td class="ltr">{{ iqd($e->price_paid, false) }}</td>
                    <td>{{ $e->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="text-align:center">لا توجد اشتراكات بعد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
