@extends('layouts.app')

@section('title', 'نوبت‌یار — نمونهٔ سامانهٔ نوبت‌دهی')

@section('content')
    <section class="hero">
        <h1>نوبت‌دهی آنلاین برای آرایشگاه و کلینیک</h1>
        <p class="lead">
            هر کسب‌وکار صفحهٔ رزرو اختصاصی خودش را دارد، با ساعات کاری هر متخصص،
            مدت و فاصلهٔ پاک‌سازی هر خدمت و جلوگیری از رزرو همزمان.
        </p>
    </section>

    <section class="panel">
        <h2>کسب‌وکارهای نمونه</h2>
        <p class="muted">این‌ها دادهٔ نمایشی هستند و با <code>php artisan migrate --seed</code> ساخته شده‌اند.</p>

        <div class="tenant-grid">
            @forelse ($tenants as $item)
                <a class="tenant-card" href="{{ route('public.booking', ['tenant' => $item->slug]) }}">
                    <strong>{{ $item->name }}</strong>
                    <span class="muted">{{ $item->address ?: '—' }}</span>
                    <span class="tenant-slug" dir="ltr">/{{ $item->slug }}</span>
                </a>
            @empty
                <p class="muted">هنوز کسب‌وکاری ثبت نشده است. ابتدا <code>php artisan migrate --seed</code> را اجرا کنید.</p>
            @endforelse
        </div>
    </section>

    <section class="grid-2">
        <div class="panel">
            <h2>چه چیزی داخلش هست؟</h2>
            <ul class="feature-list">
                <li>جداسازی کامل دادهٔ هر کسب‌وکار (scope سراسری + تست نشت داده)</li>
                <li>موتور زمان‌های آزاد با مدت خدمت، بافر و چند بازهٔ کاری در روز</li>
                <li>جلوگیری از رزرو همزمان با تراکنش و قفل ردیف</li>
                <li>پنل مدیریت: نوبت‌ها، خدمات، تیم و ساعات کاری</li>
                <li>پیامک تأیید با درایور لاگ محلی (بدون هزینه و کلید)</li>
            </ul>
        </div>

        <div class="panel">
            <h2>ورود به پنل نمونه</h2>
            <table class="table compact">
                <thead>
                    <tr><th>نقش</th><th>ایمیل</th><th>رمز</th></tr>
                </thead>
                <tbody>
                    <tr><td>مدیر آرایشگاه رز</td><td dir="ltr">admin@rose.test</td><td dir="ltr">password</td></tr>
                    <tr><td>مدیر کلینیک مهر</td><td dir="ltr">admin@mehr.test</td><td dir="ltr">password</td></tr>
                    <tr><td>مدیر پلتفرم</td><td dir="ltr">admin@nobatyar.test</td><td dir="ltr">password</td></tr>
                </tbody>
            </table>
            <p><a class="button" href="{{ route('login') }}">ورود</a></p>
        </div>
    </section>
@endsection
