<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · غني أكاديمي</title>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Readex+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <div class="auth-wrap" style="min-height:100vh">
        <div class="auth-card" style="text-align:center">
            <img class="logo" src="/img/icon.png" alt="">
            <p class="tiny ltr" style="margin:0">@yield('code')</p>
            <h1>@yield('title')<span class="dot">.</span></h1>
            <p>@yield('message')</p>
            <a href="/" class="btn btn-primary">الرئيسية</a>
        </div>
    </div>
</body>
</html>
