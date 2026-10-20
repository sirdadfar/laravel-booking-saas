@extends('layouts.app')

@section('title', 'ورود مدیران')

@section('content')
    <div class="narrow">
        <div class="card card--gold reveal">
            <div class="brand" style="margin-bottom: var(--s-5);">
                <span class="brand__mark" aria-hidden="true">ن</span>
                <span class="brand__text">
                    <strong>نوبت‌یار</strong>
                    <small>پنل مدیریت کسب‌وکار</small>
                </span>
            </div>

            <span class="eyebrow">ورود امن</span>
            <h1>ورود مدیران</h1>
            <p class="muted">این بخش برای مدیر کسب‌وکار و مدیر پلتفرم است. مشتریان بدون حساب کاربری نوبت می‌گیرند.</p>

            <form method="post" action="{{ route('login.store') }}" class="form">
                @csrf

                <div class="field">
                    <label for="email">ایمیل</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="input"
                        value="{{ old('email') }}"
                        dir="ltr"
                        inputmode="email"
                        autocomplete="email"
                        placeholder="admin@rose.test"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">رمز عبور</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input"
                        dir="ltr"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <label class="checkbox">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    مرا به خاطر بسپار
                </label>

                <button type="submit" class="btn btn--gold btn--block">
                    <x-icon name="arrow-left" class="icon icon--sm" />
                    ورود
                </button>
            </form>

            <div class="card card--plain" style="margin-top: var(--s-6); padding: var(--s-5);">
                <span class="field__label">حساب‌های نمایشی</span>
                <p class="muted small" style="margin: var(--s-2) 0 0;">
                    رمز هر سه حساب <code dir="ltr">password</code> است. فهرست نقش‌ها و ایمیل‌ها در
                    <a class="link" href="{{ route('home') }}">صفحهٔ اصلی</a> آمده است.
                </p>
            </div>
        </div>
    </div>
@endsection
