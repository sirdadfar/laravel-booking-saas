@php
    $cssVersion = is_file(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : 1;
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — نوبت‌یار</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ $cssVersion }}">
</head>
<body>
<main class="page container" id="main">
    <div class="narrow">
        <div class="card card--gold">
            <span class="eyebrow">خطای {{ $code }}</span>
            <h1>{{ $title }}</h1>
            <p class="muted">{{ $message }}</p>
            <p style="margin-top: var(--s-6);">
                <a class="btn btn--gold" href="{{ route('home') }}">بازگشت به صفحهٔ اصلی</a>
            </p>
        </div>
    </div>
</main>
</body>
</html>
