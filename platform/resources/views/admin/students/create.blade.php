@extends('layouts.admin')
@section('heading', 'حساب جديد')

@section('content')
<form class="panel" style="max-width:640px" method="POST" action="{{ route('admin.students.store') }}">
    @csrf
    <div class="form-grid">
        <div class="field"><label for="name">الاسم</label><input class="input" id="name" name="name" value="{{ old('name') }}" required></div>
        <div class="field"><label for="phone">رقم الهاتف</label><input class="input" id="phone" name="phone" type="tel" inputmode="tel" dir="ltr" value="{{ old('phone') }}" placeholder="07XXXXXXXXX" required></div>
        <div class="field"><label for="password">كلمة المرور</label><input class="input" id="password" name="password" dir="ltr" minlength="8" required></div>
        <div class="field"><label for="role">النوع</label><select class="input" id="role" name="role">@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected(old('role', request('role', 'student')) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label for="reseller_discount">خصم الموزع %</label><input class="input" id="reseller_discount" name="reseller_discount" type="number" min="0" max="60" dir="ltr" value="{{ old('reseller_discount', setting('reseller_discount')) }}"><span class="help">للموزعين فقط.</span></div>
    </div>
    <button class="btn btn-primary" type="submit">إنشاء</button>
</form>
@endsection
