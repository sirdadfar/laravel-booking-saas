@extends('layouts.app')

@section('title', 'سامانهٔ نوبت‌دهی چند‌مستأجری')
@section('description', 'نمونهٔ متن‌باز یک سامانهٔ نوبت‌دهی آنلاین چند‌مستأجری برای آرایشگاه و کلینیک: موتور زمان آزاد، جداسازی کامل دادهٔ هر کسب‌وکار و پنل فارسی راست‌به‌چپ.')

@section('content')
    <section class="hero">
        <span class="eyebrow reveal">Laravel 12 · multi-tenant</span>
        <h1 class="hero__title reveal">
            نوبت‌دهی آنلاین برای
            <em>آرایشگاه و کلینیک</em>
        </h1>
        <p class="hero__lead reveal">
            هر کسب‌وکار صفحهٔ رزرو اختصاصی خودش را دارد؛ ساعات کاری هر متخصص، مدت و فاصلهٔ
            پاک‌سازی هر خدمت و نوبت‌های موجود در یک موتور واحد حساب می‌شوند تا هیچ اسلاتی
            دو بار فروخته نشود.
        </p>

        <div class="hero__actions reveal">
            <a class="btn btn--gold" href="#businesses">
                <x-icon name="grid" class="icon icon--sm" />
                کسب‌وکارهای نمونه
            </a>
            <a class="btn btn--ghost" href="{{ route('login') }}">
                <x-icon name="user" class="icon icon--sm" />
                ورود به پنل مدیریت
            </a>
        </div>

        <div class="hero__facts reveal">
            <div class="fact">
                <b>۸۶</b>
                <span>تست خودکار</span>
            </div>
            <div class="fact">
                <b>۳</b>
                <span>نسخهٔ PHP در CI</span>
            </div>
            <div class="fact">
                <b>۰</b>
                <span>رزرو تکراری در تست هم‌زمانی</span>
            </div>
            <div class="fact">
                <b>RTL</b>
                <span>رابط فارسی، بدون فریم‌ورک CSS</span>
            </div>
        </div>
    </section>

    <section id="businesses" class="stack" aria-labelledby="businesses-title">
        <div class="page__head reveal">
            <div>
                <span class="eyebrow">دموی زنده</span>
                <h2 id="businesses-title">کسب‌وکارهای نمونه</h2>
                <p class="muted">
                    این‌ها دادهٔ نمایشی هستند و با <code>php artisan migrate --seed</code> ساخته شده‌اند.
                </p>
            </div>
        </div>

        <div class="tenant-grid">
            @forelse ($tenants as $item)
                <a class="tenant-card reveal" href="{{ route('public.booking', ['tenant' => $item->slug]) }}">
                    <span class="avatar" aria-hidden="true">
                        {{ \Illuminate\Support\Str::substr($item->name, 0, 1) }}
                    </span>
                    <strong>{{ $item->name }}</strong>
                    <span class="muted small">
                        <x-icon name="map-pin" class="icon icon--sm" />
                        {{ $item->address ?: 'آدرس ثبت نشده' }}
                    </span>
                    <span class="tenant-card__slug">/{{ $item->slug }}</span>
                    <span class="tenant-card__go">
                        مشاهدهٔ صفحهٔ رزرو
                        <x-icon name="arrow-left" class="icon icon--sm" />
                    </span>
                </a>
            @empty
                <div class="empty">
                    هنوز کسب‌وکاری ثبت نشده است. ابتدا <code>php artisan migrate --seed</code> را اجرا کنید.
                </div>
            @endforelse
        </div>
    </section>

    <section class="stack" aria-labelledby="flow-title">
        <div class="page__head reveal">
            <div>
                <span class="eyebrow">مسیر مشتری</span>
                <h2 id="flow-title">سه قدم تا نوبت</h2>
            </div>
        </div>

        <div class="grid grid--3">
            <article class="card reveal">
                <span class="step__num" aria-hidden="true">۱</span>
                <h3>خدمت و متخصص</h3>
                <p class="muted small">
                    مشتری خدمت را انتخاب می‌کند و می‌تواند متخصص مشخصی بخواهد یا بگذارد
                    سیستم اولین فرد آزاد را انتخاب کند.
                </p>
            </article>
            <article class="card reveal">
                <span class="step__num" aria-hidden="true">۲</span>
                <h3>زمان آزاد واقعی</h3>
                <p class="muted small">
                    زمان‌ها از ساعات کاری، مدت خدمت، بافر پاک‌سازی، مرخصی‌ها و نوبت‌های موجود
                    محاسبه می‌شوند؛ نه از یک لیست دستی.
                </p>
            </article>
            <article class="card reveal">
                <span class="step__num" aria-hidden="true">۳</span>
                <h3>ثبت و پیگیری</h3>
                <p class="muted small">
                    ثبت نوبت داخل تراکنش انجام می‌شود، کد پیگیری صادر می‌شود و مشتری تا مهلت
                    تعیین‌شده می‌تواند خودش نوبت را لغو کند.
                </p>
            </article>
        </div>
    </section>

    <section class="split">
        <div class="card reveal">
            <h2>چه چیزی داخلش هست؟</h2>
            <ul class="feature-list">
                <li>جداسازی کامل دادهٔ هر کسب‌وکار با scope سراسری و تست نشت داده</li>
                <li>موتور زمان‌های آزاد با مدت خدمت، بافر و چند بازهٔ کاری در روز</li>
                <li>جلوگیری از رزرو هم‌زمان با تراکنش، قفل ردیف و بازبینی پس از قفل</li>
                <li>پنل مدیریت: داشبورد، نوبت‌ها، خدمات، تیم و ساعات کاری</li>
                <li>پیامک تأیید با کانال Notification و درایور لاگ محلی (بدون هزینه و کلید)</li>
            </ul>
        </div>

        <div class="card card--gold reveal">
            <h2>ورود به پنل نمونه</h2>
            <p class="muted small">رمز هر سه حساب <code>password</code> است.</p>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">نقش</th>
                        <th scope="col">ایمیل</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td data-label="نقش">مدیر آرایشگاه رز</td>
                        <td data-label="ایمیل" dir="ltr">admin@rose.test</td>
                    </tr>
                    <tr>
                        <td data-label="نقش">مدیر کلینیک مهر</td>
                        <td data-label="ایمیل" dir="ltr">admin@mehr.test</td>
                    </tr>
                    <tr>
                        <td data-label="نقش">مدیر پلتفرم</td>
                        <td data-label="ایمیل" dir="ltr">admin@nobatyar.test</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <p style="margin-top: var(--s-5); margin-bottom: 0;">
                <a class="btn btn--gold" href="{{ route('login') }}">
                    <x-icon name="user" class="icon icon--sm" />
                    ورود
                </a>
            </p>
        </div>
    </section>
@endsection
