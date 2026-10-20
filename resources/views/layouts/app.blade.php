@php
    $tenant = $tenant ?? null;
    $cssVersion = is_file(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : 1;
    $jsVersion = is_file(public_path('js/app.js')) ? filemtime(public_path('js/app.js')) : 1;
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0A0908" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#FAF9F7" media="(prefers-color-scheme: light)">
    <meta name="description" content="@yield('description', 'نوبت‌یار — سامانهٔ نوبت‌دهی آنلاین چند‌مستأجری با موتور زمان آزاد، پنل فارسی و راست‌به‌چپ.')">

    <title>@yield('title', 'نوبت‌دهی آنلاین') — {{ $tenant->name ?? 'نوبت‌یار' }}</title>

    {{-- Apply a remembered theme before first paint so the page never flashes. --}}
    <script>
        (function () {
            document.documentElement.classList.add('has-js');
            try {
                var theme = window.localStorage.getItem('nobatyar-theme');
                if (theme === 'light' || theme === 'dark') {
                    document.documentElement.setAttribute('data-theme', theme);
                }
            } catch (error) {
                /* storage unavailable: fall back to the OS preference */
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ $cssVersion }}">
</head>
<body>
<a class="skip-link" href="#main">پرش به محتوای اصلی</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand__mark" aria-hidden="true">ن</span>
            <span class="brand__text">
                <strong>{{ $tenant->name ?? 'نوبت‌یار' }}</strong>
                <small>{{ $tenant ? 'رزرو آنلاین نوبت' : 'سامانهٔ نوبت‌دهی چند‌مستأجری' }}</small>
            </span>
        </a>

        <nav class="site-nav" aria-label="ناوبری اصلی">
            @auth
                @if (auth()->user()->is_platform_admin)
                    <a class="nav-link" href="{{ route('platform.tenants') }}">
                        <x-icon name="grid" class="icon icon--sm" />
                        کسب‌وکارها
                    </a>
                @endif

                @if ($tenant && auth()->user()->canAccessTenant($tenant))
                    <a class="nav-link" href="{{ route('admin.dashboard', ['tenant' => $tenant->slug]) }}">
                        <x-icon name="chart" class="icon icon--sm" />
                        پنل مدیریت
                    </a>
                @endif

                <form method="post" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="link-button">
                        <x-icon name="logout" class="icon icon--sm" />
                        خروج
                    </button>
                </form>
            @else
                @if ($tenant)
                    <a class="nav-link" href="{{ route('public.booking', ['tenant' => $tenant->slug]) }}">
                        <x-icon name="calendar" class="icon icon--sm" />
                        رزرو نوبت
                    </a>
                @endif
                <a class="nav-link" href="{{ route('login') }}">
                    <x-icon name="user" class="icon icon--sm" />
                    ورود مدیران
                </a>
            @endauth

            <button
                type="button"
                class="icon-btn theme-toggle"
                data-theme-toggle
                aria-pressed="false"
                aria-label="تغییر حالت نمایش"
                title="تغییر حالت نمایش"
                hidden
            >
                <x-icon name="sun" class="icon icon-btn__sun" />
                <x-icon name="moon" class="icon icon-btn__moon" />
            </button>
        </nav>
    </div>
</header>

<main id="main" class="page container">
    @if (session('status'))
        <div class="alert alert--success" role="status">
            <x-icon name="check-circle" class="icon icon--lg" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div
            class="alert alert--error"
            role="alert"
            tabindex="-1"
            id="error-summary"
            aria-labelledby="error-summary-title"
        >
            <x-icon name="alert" class="icon icon--lg" />
            <div>
                <p class="alert__title" id="error-summary-title">چند مورد را باید اصلاح کنید</p>
                <ul class="error-list">
                    @foreach ($errors->getBag('default')->getMessages() as $field => $messages)
                        @foreach ($messages as $message)
                            <li data-field="{{ \Illuminate\Support\Str::before($field, '.') }}">{{ $message }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a class="brand" href="{{ route('home') }}">
                    <span class="brand__mark" aria-hidden="true">ن</span>
                    <span class="brand__text">
                        <strong>نوبت‌یار</strong>
                        <small>نوبت‌دهی آنلاین، بدون رزرو تکراری</small>
                    </span>
                </a>
                <p class="muted small" style="margin-top: var(--s-4); max-width: 42ch;">
                    نمونهٔ متن‌باز یک سامانهٔ نوبت‌دهی چند‌مستأجری: جداسازی کامل دادهٔ هر کسب‌وکار،
                    موتور زمان آزاد و پنل فارسی راست‌به‌چپ.
                </p>
            </div>

            <div>
                <h3>دسترسی سریع</h3>
                <ul>
                    <li><a href="{{ route('home') }}">صفحهٔ اصلی</a></li>
                    <li><a href="{{ route('login') }}">ورود مدیران</a></li>
                    @if ($tenant)
                        <li><a href="{{ route('public.booking', ['tenant' => $tenant->slug]) }}">صفحهٔ رزرو {{ $tenant->name }}</a></li>
                    @endif
                    <li><a href="https://github.com/sirdadfar/laravel-booking-saas" target="_blank" rel="noopener">کد پروژه در گیت‌هاب</a></li>
                </ul>
            </div>

            <div>
                <h3>مستندات</h3>
                <ul>
                    <li><a href="https://github.com/sirdadfar/laravel-booking-saas/blob/main/docs/architecture-fa.md" target="_blank" rel="noopener">یادداشت معماری</a></li>
                    <li><a href="https://github.com/sirdadfar/laravel-booking-saas/blob/main/docs/api-fa.md" target="_blank" rel="noopener">مرجع HTTP</a></li>
                    <li><a href="https://github.com/sirdadfar/laravel-booking-saas/blob/main/design-system/nobatyar/MASTER.md" target="_blank" rel="noopener">سیستم طراحی</a></li>
                </ul>
            </div>

            <div>
                <h3>فنی</h3>
                <ul>
                    <li>Laravel 12 · PHP 8.2+</li>
                    <li>تست خودکار + CI روی سه نسخهٔ PHP</li>
                    <li>جداسازی مستأجر با scope سراسری</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© {{ now()->year }} نوبت‌یار — مجوز MIT</span>
            <span>ساخته‌شده با Laravel · طراحی با سیستم طراحی اختصاصی</span>
        </div>
    </div>
</footer>

<script src="{{ asset('js/app.js') }}?v={{ $jsVersion }}" defer></script>
@stack('scripts')
</body>
</html>
