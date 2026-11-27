@extends('layouts.admin')
@section('heading', $user->name)
@section('sub')<span class="ltr">{{ $user->phone }}</span> · {{ $roles[$user->role] }} · مسجل منذ {{ $user->created_at->format('Y/m/d') }}@endsection
@section('actions')<a class="btn btn-plain btn-sm" href="https://wa.me/964{{ ltrim($user->phone, '0') }}" target="_blank">@include('partials.icon', ['n' => 'whatsapp', 'class' => 'icon icon-sm']) واتساب</a>@endsection

@section('content')
@php($admin = auth()->user()->isAdmin())
<div class="grid" style="grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:16px;align-items:start">
    <div class="stack">
        <div class="panel">
            <h2>الكورسات</h2>
            @forelse ($user->enrollments as $e)
                <div class="list-row">
                    <span style="flex:1"><strong>{{ $e->course->title ?? '—' }}</strong><span class="tiny" style="display:block">{{ \App\Models\Enrollment::SOURCES[$e->source] ?? $e->source }} · {{ $e->created_at->format('Y/m/d') }} @if ($e->expires_at) · ينتهي {{ $e->expires_at->format('Y/m/d') }}@endif</span></span>
                    @if ($e->completed_at)<span class="badge badge-green">مكتمل</span>@endif
                    @if ($admin)
                    <form method="POST" action="{{ route('admin.students.unenroll', [$user, $e]) }}" data-confirm="إلغاء اشتراك الطالب بهذا الكورس؟">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">إلغاء</button></form>
                    @endif
                </div>
            @empty
                <p class="muted small">غير مشترك بأي كورس.</p>
            @endforelse
            @if ($admin)
            <form class="row" method="POST" action="{{ route('admin.students.enroll', $user) }}" style="margin-top:14px">
                @csrf
                <select class="input" name="course_id" aria-label="الكورس">@foreach ($courses as $c)<option value="{{ $c->id }}">{{ $c->title }}</option>@endforeach</select>
                <button class="btn btn-primary" type="submit">تسجيل مجاني</button>
            </form>
            @endif
        </div>

        <div class="panel">
            <div class="row-between"><h2 style="margin:0">الأجهزة</h2>
                <form method="POST" action="{{ route('admin.students.devices.reset', $user) }}" data-confirm="تصفير الأجهزة يطلع الطالب من كل الأجهزة. متأكد؟">@csrf<button class="btn btn-ghost btn-sm" type="submit">تصفير الأجهزة</button></form>
            </div>
            @forelse ($user->devices as $d)
                <div class="list-row">
                    @include('partials.icon', ['n' => $d->platform === 'web' ? 'devices' : 'phone'])
                    <span style="flex:1">{{ $d->name ?: $d->platform }}<span class="tiny" style="display:block"><span class="ltr">{{ $d->last_ip }}</span> · {{ $d->last_seen_at?->diffForHumans() }}</span></span>
                    <form method="POST" action="{{ route('admin.devices.toggle', $d) }}">@csrf<button class="btn {{ $d->is_blocked ? 'btn-plain' : 'btn-danger' }} btn-sm" type="submit">{{ $d->is_blocked ? 'فك الحظر' : 'حظر' }}</button></form>
                </div>
            @empty
                <p class="muted small" style="margin-top:10px">لا توجد أجهزة.</p>
            @endforelse
        </div>

        <div class="panel">
            <h2>سجل المحفظة</h2>
            @forelse ($transactions as $t)
                <div class="tx">
                    <span><strong style="display:block;font-size:14.5px">{{ $t->typeLabel() }}</strong><span class="tiny">{{ $t->note }} · {{ $t->created_at->format('Y/m/d H:i') }}</span></span>
                    <span @class(['amount', 'plus' => $t->amount > 0])>{{ $t->amount > 0 ? '+' : '−' }}{{ iqd(abs($t->amount), false) }}</span>
                </div>
            @empty
                <p class="muted small">لا توجد عمليات.</p>
            @endforelse
        </div>
    </div>

    <div class="stack">
        <div class="wallet-card" style="min-height:160px">
            <span class="lbl">الرصيد</span>
            <div class="amt"><span class="ltr">{{ iqd($user->balance, false) }}</span> <small>د.ع</small></div>
        </div>
        @if ($admin)
        <form class="panel" method="POST" action="{{ route('admin.students.wallet', $user) }}">
            @csrf
            <h2>تعديل الرصيد</h2>
            <div class="pills" style="margin-bottom:12px">
                <label class="pill"><input type="radio" name="direction" value="credit" checked> إضافة</label>
                <label class="pill"><input type="radio" name="direction" value="debit"> خصم</label>
            </div>
            <div class="field"><label for="amount">المبلغ</label><input class="input" id="amount" name="amount" type="number" min="1" dir="ltr" required></div>
            <div class="field"><label for="note">ملاحظة</label><input class="input" id="note" name="note" placeholder="دفع نقدي بالمكتب"></div>
            <button class="btn btn-primary btn-block" type="submit">تنفيذ</button>
        </form>
        @endif

        <form class="panel" method="POST" action="{{ route('admin.students.update', $user) }}">
            @csrf @method('PUT')
            <h2>الحساب</h2>
            <div class="field"><label for="u_name">الاسم</label><input class="input" id="u_name" name="name" value="{{ $user->name }}" required></div>
            <div class="field"><label for="u_phone">الهاتف</label><input class="input" id="u_phone" name="phone" dir="ltr" value="{{ $user->phone }}" required></div>
            @if ($admin)
            <div class="field"><label for="u_role">النوع</label><select class="input" id="u_role" name="role">@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected($user->role === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="field"><label for="u_disc">خصم الموزع %</label><input class="input" id="u_disc" name="reseller_discount" type="number" min="0" max="60" dir="ltr" value="{{ $user->reseller_discount }}"></div>
            @endif
            <div class="field"><label for="u_pw">كلمة مرور جديدة (اختياري)</label><input class="input" id="u_pw" name="password" dir="ltr" minlength="8"></div>
            <label class="switch" style="margin-bottom:16px"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> الحساب فعال</label>
            <button class="btn btn-primary btn-block" type="submit">حفظ</button>
        </form>
    </div>
</div>
@endsection
