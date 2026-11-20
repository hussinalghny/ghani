<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#FFFFFF">
    <title>@hasSection('title')@yield('title') · @endif غني أكاديمي</title>
    <meta name="description" content="@yield('description', 'غني أكاديمي: كورسات تقنية عملية في الأمن السيبراني وبرمجة المواقع والتطبيقات. تعلم. طبق. اشتغل.')">
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Readex+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1">
    @stack('head')
</head>
@php($user = auth()->user())
<body class="{{ $user ? 'has-tabbar' : '' }} @yield('body_class')">
    @if (setting('announcement'))
        <div class="announce">{{ setting('announcement') }}</div>
    @endif

    <header class="nav">
        <div class="container">
            <a href="{{ route('home') }}" class="nav-logo" aria-label="غني أكاديمي - الرئيسية">
                <img src="{{ asset('img/logo-light.png') }}" alt="Ghani Academy" width="120" height="40">
            </a>
            <nav class="nav-links" aria-label="القائمة الرئيسية">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>الرئيسية</a>
                <a href="{{ route('courses.index') }}" @class(['active' => request()->routeIs('courses.*')])>الكورسات</a>
                <a href="{{ route('app') }}" @class(['active' => request()->routeIs('app')])>التطبيق</a>
                @auth
                    <a href="{{ route('account') }}" @class(['active' => request()->routeIs('account')])>كورساتي</a>
                    <a href="{{ route('wallet') }}" @class(['active' => request()->routeIs('wallet')])>المحفظة</a>
                    <a href="{{ route('chat.index') }}" @class(['active' => request()->routeIs('chat.*')])>الرسائل @if ($unread = $user->conversations()->sum('student_unread'))<span class="badge ltr">{{ $unread }}</span>@endif</a>
                @endauth
            </nav>
            <div class="nav-actions">
                @auth
                    <a href="{{ route('wallet') }}" class="nav-balance">@include('partials.icon', ['n' => 'wallet', 'class' => 'icon icon-sm']) <span class="ltr">{{ iqd($user->balance, false) }}</span> د.ع</a>
                    <div class="menu" data-menu>
                        <button class="avatar" type="button" aria-label="قائمة الحساب" data-menu-btn>{{ mb_substr($user->name, 0, 1) }}</button>
                        <div class="menu-panel">
                            <div class="menu-head"><strong>{{ $user->name }}</strong><span class="tiny ltr">{{ $user->phone }}</span></div>
                            @if ($user->isAdmin())
                                <a href="{{ route('admin.dashboard') }}">@include('partials.icon', ['n' => 'dashboard']) لوحة التحكم</a>
                            @endif
                            @if ($user->isStaff())
                                <a href="{{ route('teach') }}">@include('partials.icon', ['n' => 'dashboard']) لوحة المدرب</a>
                                <a href="{{ route('inbox') }}">@include('partials.icon', ['n' => 'whatsapp']) صندوق المحادثات</a>
                            @endif
                            <a href="{{ route('chat.index') }}">@include('partials.icon', ['n' => 'whatsapp']) الرسائل والدعم</a>
                            @if ($user->isReseller())
                                <a href="{{ route('reseller.index') }}">@include('partials.icon', ['n' => 'ticket']) بوابة الموزعين</a>
                            @endif
                            <a href="{{ route('account') }}">@include('partials.icon', ['n' => 'play-circle']) كورساتي</a>
                            <a href="{{ route('wallet') }}">@include('partials.icon', ['n' => 'wallet']) المحفظة والقسائم</a>
                            <a href="{{ route('certificates.index') }}">@include('partials.icon', ['n' => 'award']) شهاداتي</a>
                            <a href="{{ route('account.settings') }}">@include('partials.icon', ['n' => 'settings']) الإعدادات والأجهزة</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">@include('partials.icon', ['n' => 'logout']) تسجيل الخروج</button></form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-plain btn-sm">دخول</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">حساب جديد</a>
                @endauth
            </div>
        </div>
    </header>

    @include('partials.flash')

    <main>
        @yield('content')
    </main>

    @unless (View::hasSection('no_footer'))
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <img class="logo" src="{{ asset('img/logo-light.png') }}" alt="Ghani Academy">
                    <p>منصة تعليمية تقنية تابعة لغني كود. نحول المتعلم إلى شخص يشتغل خلال شهر.</p>
                    <p class="tiny">تعلم. طبق. اشتغل.</p>
                </div>
                <div>
                    <h4>المنصة</h4>
                    <a href="{{ route('courses.index') }}">كل الكورسات</a>
                    <a href="{{ route('wallet') }}">شحن الرصيد بقسيمة</a>
                    <a href="{{ route('certificates.index') }}">الشهادات</a>
                    <a href="{{ route('app') }}">تنزيل التطبيق</a>
                    <a href="{{ route('privacy') }}">سياسة الخصوصية</a>
                    <a href="{{ route('terms') }}">شروط الاستخدام</a>
                </div>
                <div>
                    <h4>تواصل</h4>
                    <a href="https://wa.me/964{{ ltrim(config('academy.whatsapp'), '0') }}" target="_blank" rel="noopener">واتساب: <span class="ltr">0776 663 7444</span></a>
                    <a href="mailto:Info@ghanicode.com"><span class="ltr">Info@ghanicode.com</span></a>
                    <a href="https://ghanicode.com" target="_blank" rel="noopener"><span class="ltr">ghanicode.com</span></a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>جميع الحقوق محفوظة لغني أكاديمي · <span class="ltr">{{ date('Y') }}</span></span>
                <span>من شركة غني كود لتكنولوجيا المعلومات والاتصالات</span>
            </div>
        </div>
    </footer>
    @endunless

    @auth
    <nav class="tabbar" aria-label="التنقل السريع">
        <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>@include('partials.icon', ['n' => 'home'])الرئيسية</a>
        <a href="{{ route('courses.index') }}" @class(['active' => request()->routeIs('courses.*')])>@include('partials.icon', ['n' => 'search'])تصفح</a>
        <a href="{{ route('account') }}" @class(['active' => request()->routeIs('account', 'learn.*')])>@include('partials.icon', ['n' => 'play-circle'])كورساتي</a>
        <a href="{{ route('wallet') }}" @class(['active' => request()->routeIs('wallet')])>@include('partials.icon', ['n' => 'wallet'])المحفظة</a>
        <a href="{{ route('account.settings') }}" @class(['active' => request()->routeIs('account.settings', 'certificates.*')])>@include('partials.icon', ['n' => 'user'])حسابي</a>
    </nav>
    @endauth

    <script src="{{ asset('js/app.js') }}?v=1" defer></script>
    @stack('scripts')
</body>
</html>
