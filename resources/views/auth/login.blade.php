@extends('layouts.app')

@section('title', 'ورود مدیران')

@section('content')
    <div class="narrow">
        <div class="panel">
            <h1>ورود مدیران</h1>
            <p class="muted">این بخش برای مدیر کسب‌وکار و مدیر پلتفرم است. مشتریان بدون حساب کاربری نوبت می‌گیرند.</p>

            <form method="post" action="{{ route('login.store') }}" class="form">
                @csrf

                <label for="email">ایمیل</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" dir="ltr" required autofocus>

                <label for="password">رمز عبور</label>
                <input type="password" id="password" name="password" dir="ltr" required>

                <label class="checkbox">
                    <input type="checkbox" name="remember" value="1">
                    مرا به خاطر بسپار
                </label>

                <button type="submit" class="button">ورود</button>
            </form>

            <p class="muted small">حساب‌های نمایشی در صفحهٔ اصلی فهرست شده‌اند.</p>
        </div>
    </div>
@endsection
