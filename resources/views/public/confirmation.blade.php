@extends('layouts.app')

@section('title', 'نوبت '.$booking->code)

@section('content')
    <div class="narrow">
        <div class="panel success-panel">
            <span class="success-mark">✓</span>
            <h1>نوبت شما ثبت شد</h1>
            <p class="muted">کد پیگیری را نگه دارید. برای دیدن همین صفحه همیشه می‌توانید از همین کد استفاده کنید.</p>

            <dl class="details">
                <div><dt>کد پیگیری</dt><dd><strong dir="ltr">{{ $booking->code }}</strong></dd></div>
                <div><dt>خدمت</dt><dd>{{ $booking->service?->name }}</dd></div>
                <div><dt>متخصص</dt><dd>{{ $booking->staff?->name }}</dd></div>
                <div><dt>زمان</dt><dd>{{ $startsAt->format('Y/m/d') }} ساعت {{ $startsAt->format('H:i') }}</dd></div>
                <div><dt>مدت</dt><dd>{{ $booking->durationMinutes() }} دقیقه</dd></div>
                <div><dt>مبلغ</dt><dd>{{ number_format($booking->price) }} تومان</dd></div>
                <div><dt>وضعیت</dt><dd><span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span></dd></div>
                <div><dt>نام</dt><dd>{{ $booking->customer?->name }}</dd></div>
                <div><dt>آدرس</dt><dd>{{ $tenant->address ?: '—' }}</dd></div>
            </dl>

            @if ($canCancel)
                <form method="post" action="{{ route('public.cancel', ['tenant' => $tenant->slug, 'code' => $booking->code]) }}">
                    @csrf
                    <button type="submit" class="button button-danger" onclick="return confirm('نوبت لغو شود؟')">لغو نوبت</button>
                </form>
                <p class="muted small">
                    لغو تا {{ $tenant->cancellationWindowHours() }} ساعت قبل از زمان نوبت امکان‌پذیر است.
                </p>
            @elseif ($booking->status->isBlocking())
                <p class="muted small">مهلت لغو آنلاین این نوبت گذشته است. برای تغییر با کسب‌وکار تماس بگیرید.</p>
            @endif
        </div>

        <p><a href="{{ route('public.booking', ['tenant' => $tenant->slug]) }}">رزرو نوبت دیگر</a></p>
    </div>
@endsection
