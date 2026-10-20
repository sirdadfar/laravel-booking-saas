<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($tenant = $tenant ?? null)
    <title>@yield('title', 'نوبت‌یار') — {{ $tenant->name ?? 'نوبت‌یار' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand-mark">ن</span>
            <span>
                <strong>{{ $tenant->name ?? 'نوبت‌یار' }}</strong>
                <small>{{ $tenant ? 'رزرو آنلاین نوبت' : 'سامانهٔ نوبت‌دهی چند‌مستأجری' }}</small>
            </span>
        </a>

        <nav class="site-nav">
            @auth
                @if (auth()->user()->is_platform_admin)
                    <a href="{{ route('platform.tenants') }}">کسب‌وکارها</a>
                @endif
                @if ($tenant && auth()->user()->canAccessTenant($tenant))
                    <a href="{{ route('admin.dashboard', ['tenant' => $tenant->slug]) }}">پنل مدیریت</a>
                @endif
                <form method="post" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="link-button">خروج</button>
                </form>
            @else
                <a href="{{ route('login') }}">ورود مدیران</a>
            @endauth
        </nav>
    </div>
</header>

<main class="container">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error" role="alert">
            <ul class="error-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <p>نوبت‌یار — نمونهٔ متن‌باز یک سامانهٔ نوبت‌دهی چند‌مستأجری با Laravel.</p>
    </div>
</footer>

@stack('scripts')
</body>
</html>
