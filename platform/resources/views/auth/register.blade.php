@extends('layouts.app')
@section('title', 'حساب جديد')
@section('no_footer', true)

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <img class="logo" src="{{ asset('img/icon.png') }}" alt="">
        <h1>ابدأ مهنتك هذا الشهر<span class="dot">.</span></h1>
        <p>حساب واحد للموقع والتطبيق.</p>
        <form method="POST" action="{{ route('register') }}" data-once>
            @csrf
            <div class="field">
                <label for="name">الاسم الكامل</label>
                <input class="input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
                <span class="help">يظهر على شهادة الإتمام، اكتبه كاملا.</span>
                @error('name')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="phone">رقم الهاتف</label>
                <input class="input @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel" inputmode="tel" dir="ltr" autocomplete="tel" value="{{ old('phone') }}" placeholder="07XXXXXXXXX" required>
                @error('phone')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="city">المحافظة</label>
                <input class="input" id="city" name="city" value="{{ old('city') }}" placeholder="بغداد">
            </div>
            <div class="field">
                <label for="password">كلمة المرور</label>
                <input class="input @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" dir="ltr" minlength="8" required>
                <span class="help">8 أحرف على الأقل.</span>
                @error('password')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">تأكيد كلمة المرور</label>
                <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" dir="ltr" required>
            </div>
            <button class="btn btn-primary btn-block" type="submit">إنشاء الحساب</button>
            <p class="tiny" style="text-align:center;margin:14px 0 0">الحساب شخصي ويشتغل على <span class="ltr">{{ setting('max_devices') }}</span> أجهزة كحد أعلى.</p>
        </form>
        <p class="auth-alt">عندك حساب؟ <a href="{{ route('login') }}">سجل دخولك</a></p>
    </div>
</div>
@endsection
