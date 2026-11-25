@extends('layouts.admin')
@section('heading', $cohort->title.' · حضوري')
@section('sub', 'المسجلين، التأكيد، الحضور، وتفاصيل الصفحة')

@section('content')
<div class="kpis">
    <div class="kpi"><span>مقاعد مؤكدة</span><strong class="ltr">{{ $stats['paid'] }} / {{ $cohort->seats }}</strong></div>
    <div class="kpi"><span>بانتظار الدفع</span><strong class="ltr">{{ $stats['pending'] }}</strong><em>محجوزة <span class="ltr">48</span> ساعة</em></div>
    <div class="kpi"><span>قائمة الانتظار</span><strong class="ltr">{{ $stats['waitlist'] }}</strong></div>
    <div class="kpi"><span>الإيراد</span><strong class="ltr">{{ number_format($stats['revenue']) }}</strong><em>حضروا: <span class="ltr">{{ $stats['checked'] }}</span></em></div>
</div>

<div class="panel" style="margin-bottom:16px">
    <div class="row-between" style="flex-wrap:wrap;gap:10px">
        <h2 style="margin:0">المسجلين</h2>
        <div class="row" style="gap:6px;flex-wrap:wrap">
            @foreach (['all' => 'الكل', 'paid' => 'مؤكد', 'pending' => 'بانتظار الدفع', 'waitlist' => 'الانتظار', 'cancelled' => 'ملغي'] as $k => $v)<a href="{{ route('admin.cohorts.show', [$cohort, 'status' => $k]) }}" @class(['pill', 'active' => $status === $k])>{{ $v }}</a>@endforeach
            <a class="btn btn-plain btn-sm" href="{{ route('admin.cohorts.csv', $cohort) }}">@include('partials.icon', ['n' => 'download', 'class' => 'icon icon-sm']) تصدير Excel</a>
            <a class="btn btn-plain btn-sm" href="{{ route('cohort.show', $cohort) }}" target="_blank">@include('partials.icon', ['n' => 'external', 'class' => 'icon icon-sm']) الصفحة</a>
        </div>
    </div>
    <form method="GET" class="row" style="margin-top:12px;gap:8px"><input type="hidden" name="status" value="{{ $status }}"><input class="input" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو الهاتف أو رمز التذكرة" aria-label="بحث" style="flex:1"><button class="btn btn-plain" type="submit">بحث</button></form>
    <div class="table-wrap" style="border:0;margin-top:12px">
        <table class="table">
            <thead><tr><th>الرمز</th><th>الاسم</th><th>الهاتف</th><th>المستوى</th><th>الحالة</th><th>التسجيل</th><th>حضور</th><th></th></tr></thead>
            <tbody>
            @forelse ($regs as $r)
                <tr>
                    <td class="ltr"><a href="{{ route('cohort.ticket', $r) }}" target="_blank">{{ $r->code }}</a></td>
                    <td><strong>{{ $r->name }}</strong>@if ($r->job)<div class="tiny muted">{{ $r->job }}</div>@endif @if ($r->notes)<div class="tiny muted">{{ \Illuminate\Support\Str::limit($r->notes, 60) }}</div>@endif</td>
                    <td class="ltr"><a href="https://wa.me/964{{ ltrim($r->phone, '0') }}" target="_blank" rel="noopener">{{ $r->phone }}</a></td>
                    <td class="tiny">{{ \App\Models\CohortRegistration::LEVELS[$r->level] ?? '—' }}</td>
                    <td><span @class(['badge', 'badge-green' => $r->status === 'paid', 'badge-gold' => $r->status === 'pending', 'badge-gray' => in_array($r->status, ['cancelled', 'waitlist'])])>{{ \App\Models\CohortRegistration::STATUSES[$r->status] }}{{ $r->paid_by === 'auto' ? ' · تلقائي' : '' }}</span></td>
                    <td class="tiny ltr">{{ $r->created_at->timezone('Asia/Baghdad')->format('m/d H:i') }}</td>
                    <td>@if ($r->status === 'paid')<form method="POST" action="{{ route('admin.cohorts.act', [$r, 'checkin']) }}">@csrf<button class="btn btn-sm {{ $r->checked_in_at ? 'btn-primary' : 'btn-plain' }}" type="submit">{{ $r->checked_in_at ? 'حضر' : 'تسجيل حضور' }}</button></form>@endif</td>
                    <td class="row" style="gap:4px">
                        @if (in_array($r->status, ['pending', 'waitlist', 'cancelled']))<form method="POST" action="{{ route('admin.cohorts.act', [$r, 'paid']) }}" data-confirm="تأكيد المقعد يدويا (دفع كاش أو تحويل تأكدت منه)؟">@csrf<button class="btn btn-plain btn-sm" type="submit">تأكيد يدوي</button></form>@endif
                        @if ($r->status === 'waitlist')<form method="POST" action="{{ route('admin.cohorts.act', [$r, 'pending']) }}">@csrf<button class="btn btn-plain btn-sm" type="submit">انطيه مقعد</button></form>@endif
                        @if ($r->status !== 'cancelled')<form method="POST" action="{{ route('admin.cohorts.act', [$r, 'cancel']) }}" data-confirm="إلغاء هذا التسجيل؟">@csrf<button class="btn btn-plain btn-sm" type="submit">إلغاء</button></form>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">ما أكو تسجيلات بعد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $regs->links() }}
</div>

<form class="panel stack" method="POST" action="{{ route('admin.cohorts.update', $cohort) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <h2>تفاصيل الصفحة</h2>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
        <div class="field"><label for="c-title">العنوان</label><input class="input" id="c-title" name="title" value="{{ old('title', $cohort->title) }}" required></div>
        <div class="field"><label for="c-sub">العنوان الفرعي</label><input class="input" id="c-sub" name="subtitle" value="{{ old('subtitle', $cohort->subtitle) }}"></div>
        <div class="field"><label for="c-price">السعر (د.ع)</label><input class="input ltr" id="c-price" name="price" type="number" dir="ltr" value="{{ old('price', $cohort->price) }}" required></div>
        <div class="field"><label for="c-seats">عدد المقاعد</label><input class="input ltr" id="c-seats" name="seats" type="number" dir="ltr" value="{{ old('seats', $cohort->seats) }}" required></div>
        <div class="field"><label for="c-date">تاريخ البداية</label><input class="input ltr" id="c-date" name="starts_on" type="date" dir="ltr" value="{{ old('starts_on', $cohort->starts_on?->toDateString()) }}"></div>
        <div class="field"><label for="c-sch">الأوقات</label><input class="input" id="c-sch" name="schedule" value="{{ old('schedule', $cohort->schedule) }}" placeholder="مثلا: السبت والثلاثاء · 6 إلى 8 مساء"></div>
        <div class="field"><label for="c-loc">المكان</label><input class="input" id="c-loc" name="location" value="{{ old('location', $cohort->location) }}" placeholder="مثلا: بغداد · الكرادة · قاعة ..."></div>
        <div class="field"><label for="c-map">رابط الموقع على الخريطة</label><input class="input ltr" id="c-map" name="map_url" dir="ltr" value="{{ old('map_url', $cohort->map_url) }}"></div>
        <div class="field"><label for="c-course">الكورس المرتبط بالمنصة</label><select class="input" id="c-course" name="course_id"><option value="">—</option>@foreach ($courses as $co)<option value="{{ $co->id }}" @selected($cohort->course_id == $co->id)>{{ $co->title }}</option>@endforeach</select></div>
    </div>
    <label class="row" style="gap:8px"><input type="checkbox" name="is_open" value="1" @checked($cohort->is_open)> التسجيل مفتوح</label>

    @php($lines = fn ($k) => implode("\n", (array) ($cohort->details[$k] ?? [])))
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px">
        <div class="field"><label for="c-out">شنو راح يتعلم (سطر لكل نقطة)</label><textarea class="input" id="c-out" name="outcomes" rows="6">{{ $lines('outcomes') }}</textarea></div>
        <div class="field"><label for="c-weeks">المنهج (سطر لكل أسبوع: العنوان | الشرح)</label><textarea class="input" id="c-weeks" name="weeks" rows="6">{{ $lines('weeks') }}</textarea></div>
        <div class="field"><label for="c-aud">لمن هذا الكورس</label><textarea class="input" id="c-aud" name="audience" rows="5">{{ $lines('audience') }}</textarea></div>
        <div class="field"><label for="c-inc">الكورس يشمل</label><textarea class="input" id="c-inc" name="includes" rows="5">{{ $lines('includes') }}</textarea></div>
        <div class="field"><label for="c-par">التعاونات والشركاء (الاسم | دوره)</label><textarea class="input" id="c-par" name="partners" rows="5">{{ $lines('partners') }}</textarea><small class="tiny">شعار كل شريك بنفس ترتيبه:</small>@foreach ($cohort->d('partners') as $i => $p)<div class="row tiny" style="gap:8px;margin-top:4px"><span style="min-width:140px">{{ \App\Models\Cohort::pair($p)[0] }}</span><input type="file" name="logos[{{ $i }}]" accept="image/*"></div>@endforeach</div>
        <div class="field"><label for="c-faq">الأسئلة الشائعة (السؤال | الجواب)</label><textarea class="input" id="c-faq" name="faq" rows="6">{{ $lines('faq') }}</textarea></div>
        <div class="field"><label for="c-ins">نبذة المدرب (اختياري، ينعرض بدون اسم إذا ما كتبته)</label><textarea class="input" id="c-ins" name="instructor_note" rows="3">{{ $cohort->details['instructor'] ?? '' }}</textarea></div>
        <div class="field"><label for="c-img">الصورة الرئيسية</label><input type="file" id="c-img" name="image" accept="image/*"><label for="c-gal" style="margin-top:10px">صور إضافية (من أجواء الكورس)</label><input type="file" id="c-gal" name="gallery[]" accept="image/*" multiple><small class="tiny">الصور الحالية: <span class="ltr">{{ count($cohort->details['gallery'] ?? []) }}</span></small><label class="row tiny" style="gap:6px"><input type="checkbox" name="clear_gallery" value="1"> حذف الصور الإضافية الحالية</label></div>
    </div>
    <button class="btn btn-primary" type="submit">حفظ</button>
</form>
@endsection
