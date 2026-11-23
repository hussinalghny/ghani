@extends('layouts.admin')
@section('title', 'لوحة المدرب')
@section('heading', $instructor ? 'أهلا '.$instructor->name : 'لوحة المدرب')
@section('sub', 'نسبتك من الإيراد '.$percent.'%')
@section('actions')
    @if (auth()->user()->isAdmin())
        <form method="GET" action="{{ route('teach') }}" class="row">
            <select class="input" name="instructor" onchange="this.form.submit()" aria-label="المدرب" style="min-width:200px">
                @foreach (\App\Models\Instructor::orderBy('name')->get(['id', 'name']) as $i)<option value="{{ $i->id }}" @selected($instructor?->id === $i->id)>{{ $i->name }}</option>@endforeach
            </select>
        </form>
    @endif
@endsection

@section('content')
@if (! $instructor)
    <div class="panel"><p class="muted">حسابك غير مربوط بملف مدرب بعد. اطلب من الإدارة ربطه من صفحة المدربين.</p></div>
@else
<div class="kpis">
    <div class="kpi"><span>الطلاب</span><strong class="ltr">{{ number_format($stats['students']) }}</strong><em>+<span class="ltr">{{ $stats['month'] }}</span> اشتراك هذا الشهر</em></div>
    <div class="kpi"><span>إيراد كورساتك</span><strong class="ltr">{{ iqd($stats['revenue'], false) }}</strong><em style="color:var(--text-3)">هذا الشهر <span class="ltr">{{ iqd($stats['month_revenue'], false) }}</span></em></div>
    <div class="kpi"><span>نسبتك (<span class="ltr">{{ $percent }}%</span>)</span><strong class="ltr" style="color:var(--violet)">{{ iqd($stats['share'], false) }}</strong><em>هذا الشهر <span class="ltr">{{ iqd($stats['month_share'], false) }}</span></em></div>
    <div class="kpi"><span>نشطين آخر 7 أيام</span><strong class="ltr">{{ number_format($stats['active_week']) }}</strong><em style="color:var(--text-3)"><span class="ltr">{{ $stats['paid'] }}</span> اشتراك مدفوع من <span class="ltr">{{ $stats['enrollments'] }}</span></em></div>
</div>

<div class="grid" style="grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:16px">
    <div class="panel">
        <h2>الاشتراكات آخر 6 أشهر</h2>
        @php($max = max(1, $months->max('count')))
        <div class="chart">
            @foreach ($months as $m)
                <div @class(['b', 'today' => $loop->last]) style="height: {{ max(3, $m['count'] / $max * 100) }}%" title="{{ $m['label'] }} · {{ iqd($m['share']) }}"><span class="ltr">{{ $m['count'] ?: '' }}</span></div>
            @endforeach
        </div>
        <div class="row tiny muted" style="justify-content:space-between;margin-top:6px">@foreach ($months as $m)<span class="ltr">{{ $m['label'] }}</span>@endforeach</div>
    </div>
    <div class="panel">
        <h2>مصدر الاشتراكات</h2>
        @forelse (\App\Models\Enrollment::SOURCES as $k => $label)
            <div class="list-row"><span style="flex:1">{{ $label }}</span><span class="badge ltr">{{ $sources[$k] ?? 0 }}</span></div>
        @empty
        @endforelse
    </div>
</div>

<div class="panel" style="margin-top:16px">
    <h2>كورساتي</h2>
    <div class="table-wrap" style="border:0">
        <table class="table">
            <thead><tr><th>الكورس</th><th>الحالة</th><th>الطلاب</th><th>هذا الشهر</th><th>أكملوا</th><th>الإيراد</th><th>نسبتك</th><th></th></tr></thead>
            <tbody>
            @forelse ($courses as $c)
                <tr>
                    <td><strong>{{ $c->title }}</strong><div class="tiny muted"><span class="ltr">{{ $c->lessons_count }}</span> درس · السعر <span class="ltr">{{ iqd($c->sale_price ?? $c->price) }}</span></div></td>
                    <td>@if ($c->is_published)<span class="badge badge-green">منشور</span>@else<span class="badge badge-gray">مخفي</span>@endif</td>
                    <td class="ltr">{{ $c->enrollments_count }}</td>
                    <td class="ltr">{{ $c->month_count }}</td>
                    <td class="ltr">{{ $c->completed_count }}</td>
                    <td class="ltr">{{ iqd((int) $c->revenue, false) }}</td>
                    <td class="ltr"><strong>{{ iqd((int) round($c->revenue * $percent / 100), false) }}</strong></td>
                    <td><div class="row" style="flex-wrap:nowrap">
                        <a class="btn btn-plain btn-sm" href="{{ route('admin.courses.edit', $c) }}">تعديل</a>
                        <a class="btn btn-primary btn-sm" href="{{ route('admin.courses.curriculum', $c) }}">الدروس</a>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">ما عندك كورسات بعد. الإدارة تضيف الكورس وتربطه باسمك.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;margin-top:16px">
    <div class="panel" id="calc">
        <h2>حاسبة الأرباح</h2>
        <div class="form-grid">
            <div class="field full"><label for="cc">الكورس</label>
                <select class="input" id="cc">
                    <option value="0">سعر مخصص</option>
                    @foreach ($courses as $c)<option value="{{ $c->sale_price ?? $c->price }}" data-n="{{ $c->enrollments_count }}">{{ $c->title }}</option>@endforeach
                </select>
            </div>
            <div class="field"><label for="cp">سعر الكورس (د.ع)</label><input class="input" id="cp" type="number" min="0" step="1000" dir="ltr" inputmode="numeric" value="{{ $courses->first()?->sale_price ?? $courses->first()?->price ?? 200000 }}"></div>
            <div class="field"><label for="cn">عدد الطلاب</label><input class="input" id="cn" type="number" min="0" dir="ltr" inputmode="numeric" value="{{ max(10, $stats['enrollments']) }}"></div>
            <div class="field"><label for="cd">خصم متوسط (%)</label><input class="input" id="cd" type="number" min="0" max="100" dir="ltr" inputmode="numeric" value="0"></div>
            <div class="field"><label for="cr">نسبتك (%)</label><input class="input" id="cr" type="number" min="0" max="100" dir="ltr" inputmode="numeric" value="{{ $percent }}"></div>
        </div>
        <div class="kpis" style="grid-template-columns:repeat(3,minmax(0,1fr));margin:8px 0 0">
            <div class="kpi"><span>الإيراد الكلي</span><strong class="ltr" id="o-total">0</strong></div>
            <div class="kpi"><span>نسبتك</span><strong class="ltr" id="o-share" style="color:var(--violet)">0</strong></div>
            <div class="kpi"><span>حصة المنصة</span><strong class="ltr" id="o-platform">0</strong></div>
        </div>
        <p class="help" id="o-per" style="margin-top:8px"></p>
    </div>

    <div class="panel">
        <h2>آخر المشتركين</h2>
        @forelse ($recent as $e)
            <div class="list-row"><span style="flex:1">{{ $e->user?->name ?? 'طالب' }}<div class="tiny muted">{{ $e->course?->title }}</div></span><span class="tiny muted ltr">{{ $e->created_at->format('Y/m/d') }}</span></div>
        @empty
            <p class="muted small">لا توجد اشتراكات بعد.</p>
        @endforelse
    </div>
</div>
<div class="grid" style="grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:16px;margin-top:16px">
    <div class="panel" id="questions">
        <h2>أسئلة الطلاب <span class="badge ltr">{{ $questions->count() }}</span></h2>
        @forelse ($questions as $q)
            <div style="border-top:1px solid var(--line);padding:14px 0">
                <div class="tiny muted">{{ $q->user?->name }} · {{ $q->course?->title }} · {{ $q->lesson?->title }} · {{ $q->created_at->diffForHumans() }}</div>
                <p style="margin:6px 0 10px;white-space:pre-line">{{ $q->body }}</p>
                <form method="POST" action="{{ route('teach.answer', $q) }}" class="row" style="gap:8px;align-items:flex-start">
                    @csrf
                    <textarea class="input" name="answer" rows="2" required minlength="2" placeholder="اكتب جوابك" aria-label="الجواب" style="flex:1"></textarea>
                    <button class="btn btn-primary btn-sm" type="submit">إرسال</button>
                </form>
            </div>
        @empty
            <p class="muted">ما أكو أسئلة تنتظر جواب.</p>
        @endforelse
    </div>
    <div class="panel">
        <h2>آخر التقييمات</h2>
        @forelse ($reviews as $r)
            <div style="border-top:1px solid var(--line);padding:12px 0">
                <div class="row-between"><strong>{{ $r->user?->name }}</strong><span class="ltr" style="color:#C4710D">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span></div>
                <div class="tiny muted">{{ $r->course?->title }}</div>
                @if ($r->body)<p class="small" style="margin:6px 0 0">{{ $r->body }}</p>@endif
                @if (auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('teach.review', $r) }}" style="margin-top:6px">@csrf<button class="btn btn-plain btn-sm" type="submit">{{ $r->is_visible ? 'إخفاء' : 'إظهار' }}</button></form>
                @endif
            </div>
        @empty
            <p class="muted">ما أكو تقييمات بعد.</p>
        @endforelse
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  if (!$('calc')) return;
  var fmt = function (n) { return Math.round(n).toLocaleString('en-US'); };
  function run() {
    var p = +$('cp').value || 0, n = +$('cn').value || 0, d = Math.min(100, +$('cd').value || 0), r = Math.min(100, +$('cr').value || 0);
    var total = p * (1 - d / 100) * n, share = total * r / 100;
    $('o-total').textContent = fmt(total);
    $('o-share').textContent = fmt(share);
    $('o-platform').textContent = fmt(total - share);
    $('o-per').textContent = n ? 'نصيبك من كل طالب: ' + fmt(share / n) + ' د.ع' : '';
  }
  $('cc').addEventListener('change', function () {
    var o = this.options[this.selectedIndex];
    if (+this.value) { $('cp').value = this.value; $('cn').value = Math.max(10, +o.dataset.n || 0); }
    run();
  });
  ['cp', 'cn', 'cd', 'cr'].forEach(function (id) { $(id).addEventListener('input', run); });
  run();
})();
</script>
@endpush
