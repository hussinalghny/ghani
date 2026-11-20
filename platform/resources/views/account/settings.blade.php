@extends('layouts.app')
@section('title', 'الإعدادات')

@section('content')
<div class="container container-sm">
    <div class="page-head">
        <h1>حسابي<span class="dot">.</span></h1>
        <p>معلوماتك وأمان حسابك.</p>
    </div>

    <div class="stack">
        <div class="panel">
            <h2>المعلومات الشخصية</h2>
            <form method="POST" action="{{ route('account.update') }}">
                @csrf @method('PUT')
                <div class="field"><label for="name">الاسم الكامل</label><input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required><span class="help">يظهر على الشهادات وعلى العلامة المائية بالفيديو.</span></div>
                <div class="grid grid-2" style="gap:0 16px">
                    <div class="field"><label for="phone">رقم الهاتف</label><input class="input" id="phone" value="{{ $user->phone }}" dir="ltr" disabled><span class="help">لتغيير الرقم راسل الدعم.</span></div>
                    <div class="field"><label for="city">المحافظة</label><input class="input" id="city" name="city" value="{{ old('city', $user->city) }}"></div>
                </div>
                <div class="field"><label for="email">البريد الإلكتروني (اختياري)</label><input class="input" id="email" name="email" type="email" dir="ltr" value="{{ old('email', $user->email) }}"></div>
                <button class="btn btn-primary" type="submit">حفظ</button>
            </form>
        </div>

        <div class="panel">
            <div class="row-between"><h2 style="margin:0">الأجهزة المربوطة</h2><span class="tiny"><span class="ltr">{{ $devices->where('is_blocked', false)->count() }}/{{ setting('max_devices') }}</span> أجهزة</span></div>
            <p class="small muted" style="margin:8px 0 6px">حسابك شخصي. إذا تغير جهازك راسلنا على الواتساب حتى نعيد تعيين الأجهزة.</p>
            @forelse ($devices as $d)
                <div class="list-row">
                    <span class="cat-icon" style="width:42px;height:42px">@include('partials.icon', ['n' => $d->platform === 'web' ? 'devices' : 'phone'])</span>
                    <span style="flex:1"><strong style="display:block">{{ $d->name ?: \App\Models\Device::PLATFORMS[$d->platform] }}</strong><span class="tiny">آخر نشاط {{ $d->last_seen_at?->diffForHumans() }}</span></span>
                    @if ($d->device_id === request()->cookie('ga_did'))<span class="badge">هذا الجهاز</span>@endif
                    @if ($d->is_blocked)<span class="badge badge-red">محظور</span>@endif
                </div>
            @empty
                <p class="small muted">لا توجد أجهزة.</p>
            @endforelse
        </div>

        <div class="panel">
            <h2>تغيير كلمة المرور</h2>
            <form method="POST" action="{{ route('account.password') }}">
                @csrf @method('PUT')
                <div class="field"><label for="current_password">كلمة المرور الحالية</label><input class="input" id="current_password" name="current_password" type="password" dir="ltr" required></div>
                <div class="grid grid-2" style="gap:0 16px">
                    <div class="field"><label for="password">الجديدة</label><input class="input" id="password" name="password" type="password" dir="ltr" minlength="8" required></div>
                    <div class="field"><label for="password_confirmation">تأكيدها</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" dir="ltr" required></div>
                </div>
                <button class="btn btn-ghost" type="submit">تغيير كلمة المرور</button>
            </form>
        </div>

        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-plain btn-block" type="submit">تسجيل الخروج</button></form>

        @unless ($user->isAdmin())
        <details class="panel">
            <summary style="cursor:pointer;color:var(--danger);font-weight:600">حذف الحساب نهائيا</summary>
            <form method="POST" action="{{ route('account.destroy') }}" data-confirm="حذف الحساب نهائي ويمسح كورساتك ورصيدك وشهاداتك. متأكد؟" style="margin-top:14px">
                @csrf @method('DELETE')
                <p class="small muted">يمسح كل بياناتك: الكورسات والرصيد والشهادات والمحادثات. ما يمكن التراجع.</p>
                <div class="field"><label for="del_pw">كلمة المرور للتأكيد</label><input class="input" id="del_pw" name="password" type="password" dir="ltr" required></div>
                <button class="btn btn-danger btn-block" type="submit">احذف حسابي</button>
            </form>
        </details>
        @endunless
    </div>
    <div style="height:64px"></div>
</div>
@endsection
