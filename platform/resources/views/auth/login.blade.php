@extends('layouts.app')
@section('title', 'تسجيل الدخول')
@section('no_footer', true)

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <img class="logo" src="{{ asset('img/icon.png') }}" alt="">
        <h1>أهلا بعودتك<span class="dot">.</span></h1>
        <p>سجل دخولك وأكمل من حيث توقفت.</p>
        <form method="POST" action="{{ route('login') }}" data-once>
            @csrf
            <div class="field">
                <label for="phone">رقم الهاتف</label>
                <input class="input @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel" inputmode="tel" dir="ltr" autocomplete="tel" value="{{ old('phone') }}" placeholder="07XXXXXXXXX" required autofocus>
                @error('phone')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="password">كلمة المرور</label>
                <input class="input" id="password" name="password" type="password" autocomplete="current-password" dir="ltr" required>
            </div>
            <button class="btn btn-primary btn-block" type="submit">دخول</button>
        </form>
        <p class="auth-alt">ما عندك حساب؟ <a href="{{ route('register') }}">أنشئ حسابك</a></p>
        <p class="tiny" style="text-align:center;margin:12px 0 0">نسيت كلمة المرور؟ راسلنا على واتساب <a class="ltr" href="https://wa.me/964{{ ltrim(config('academy.whatsapp'), '0') }}">0776 663 7444</a></p>
    </div>
</div>
@endsection
