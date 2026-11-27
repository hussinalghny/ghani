<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'لوحة التحكم') · غني أكاديمي</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Readex+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=1">
</head>
<body>
<div class="admin">
    <aside class="side" id="side">
        <a class="brand" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isSupport() ? route('inbox') : route('teach')) }}"><img src="{{ asset('img/logo-light.png') }}" alt="Ghani Academy"></a>
        @php($me = auth()->user())
        @php($inboxUnread = app(\App\Services\ChatService::class)->staffScope($me)->sum('staff_unread'))
        @if ($me->isInstructor())
        <a href="{{ route('teach') }}" @class(['active' => request()->routeIs('teach', 'admin.courses.*', 'admin.lessons.*')])>@include('partials.icon', ['n' => 'dashboard']) لوحتي وكورساتي</a>
        @endif
        <a href="{{ route('inbox') }}" @class(['active' => request()->routeIs('inbox', 'chat.*')])>@include('partials.icon', ['n' => 'whatsapp']) المحادثات @if ($inboxUnread)<span class="badge badge-gold ltr" style="margin-inline-start:auto">{{ $inboxUnread }}</span>@endif</a>
        @if ($me->isSupport())
        <a href="{{ route('admin.students.index') }}" @class(['active' => request()->routeIs('admin.students.*')])>@include('partials.icon', ['n' => 'users']) الطلاب</a>
        <a href="{{ route('admin.cohorts') }}" @class(['active' => request()->routeIs('admin.cohorts*')])>@include('partials.icon', ['n' => 'users']) الكورسات الحضورية</a>
        @endif
        @if ($me->isAdmin())
        <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>@include('partials.icon', ['n' => 'dashboard']) الرئيسية</a>
        <div class="grp">المحتوى</div>
        <a href="{{ route('admin.courses.index') }}" @class(['active' => request()->routeIs('admin.courses.*', 'admin.lessons.*')])>@include('partials.icon', ['n' => 'play-circle']) الكورسات</a>
        <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>@include('partials.icon', ['n' => 'grid']) الأقسام</a>
        <a href="{{ route('admin.instructors.index') }}" @class(['active' => request()->routeIs('admin.instructors.*')])>@include('partials.icon', ['n' => 'mic']) المدربون</a>
        <div class="grp">المبيعات</div>
        <a href="{{ route('admin.payments') }}" @class(['active' => request()->routeIs('admin.payments*')])>@include('partials.icon', ['n' => 'wallet']) الدفع المباشر @if ($pp = \App\Models\PaymentNotification::where('status', 'review')->count())<span class="badge badge-gold ltr" style="margin-inline-start:auto">{{ $pp }}</span>@endif</a>
        <a href="{{ route('admin.storage') }}" @class(['active' => request()->routeIs('admin.storage*')])>@include('partials.icon', ['n' => 'layers']) تخزين الفيديو</a>
        <a href="{{ route('admin.cohorts') }}" @class(['active' => request()->routeIs('admin.cohorts*')])>@include('partials.icon', ['n' => 'users']) الكورسات الحضورية</a>
        <a href="{{ route('admin.vouchers.index') }}" @class(['active' => request()->routeIs('admin.vouchers.*')])>@include('partials.icon', ['n' => 'ticket']) القسائم</a>
        <a href="{{ route('admin.students.index') }}" @class(['active' => request()->routeIs('admin.students.*') && request('role') !== 'reseller'])>@include('partials.icon', ['n' => 'users']) الطلاب</a>
        <a href="{{ route('admin.students.index', ['role' => 'reseller']) }}" @class(['active' => request('role') === 'reseller'])>@include('partials.icon', ['n' => 'wallet']) الموزعون</a>
        <div class="grp">النظام</div>
        <a href="{{ route('admin.notifications') }}" @class(['active' => request()->routeIs('admin.notifications')])>@include('partials.icon', ['n' => 'info']) الإشعارات</a>
        <a href="{{ route('admin.app') }}" @class(['active' => request()->routeIs('admin.app')])>@include('partials.icon', ['n' => 'phone']) تطبيق الموبايل</a>
        <a href="{{ route('admin.settings') }}" @class(['active' => request()->routeIs('admin.settings')])>@include('partials.icon', ['n' => 'shield']) الحماية والإعدادات</a>
        @endif
        <div class="bottom">
            <a href="{{ route('home') }}" target="_blank">@include('partials.icon', ['n' => 'external']) عرض المنصة</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-plain btn-sm btn-block" style="margin-top:8px" type="submit">تسجيل الخروج</button></form>
        </div>
    </aside>
    <main class="admin-main">
        <div class="admin-top">
            <div class="row">
                <button class="btn btn-plain btn-sm admin-burger" type="button" onclick="document.getElementById('side').classList.toggle('open')" aria-label="القائمة">@include('partials.icon', ['n' => 'menu'])</button>
                <div>
                    <h1>@yield('heading')<span class="dot">.</span></h1>
                    @hasSection('sub')<div class="sub">@yield('sub')</div>@endif
                </div>
            </div>
            <div class="row">@yield('actions')</div>
        </div>
        @include('partials.flash')
        @yield('content')
    </main>
</div>
<script src="{{ asset('js/app.js') }}?v=1" defer></script>
@stack('scripts')
</body>
</html>
