@use('App\Support\PersianNumber')

@extends('layouts.app')

@section('title', 'نوبت '.$booking->code)

@section('content')
    <div class="narrow">
        <div class="card card--gold reveal">
            <div class="success-mark" aria-hidden="true">
                <x-icon name="check" class="icon icon--lg" style="width: 28px; height: 28px;" />
            </div>

            <span class="eyebrow">تأیید رزرو</span>
            <h1>نوبت شما ثبت شد</h1>
            <p class="muted">
                کد پیگیری را نگه دارید. برای دیدن همین صفحه همیشه می‌توانید از همین کد استفاده کنید.
            </p>

            <div class="card card--plain" style="margin-block: var(--s-6); padding: var(--s-5);">
                <span class="field__label">کد پیگیری</span>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--s-4); flex-wrap: wrap; margin-top: var(--s-2);">
                    <strong dir="ltr" style="font-family: var(--font-mono); font-size: var(--t-lg); letter-spacing: 0.08em; color: var(--gold-strong);">
                        {{ $booking->code }}
                    </strong>
                    <button
                        type="button"
                        class="btn btn--ghost btn--sm"
                        data-copy="{{ $booking->code }}"
                        aria-describedby="copy-status"
                    >
                        <x-icon name="list" class="icon icon--sm" />
                        کپی کد
                    </button>
                </div>
                <p class="field__hint" id="copy-status" role="status" aria-live="polite"></p>
            </div>

            <dl class="details">
                <div><dt>خدمت</dt><dd>{{ $booking->service?->name }}</dd></div>
                <div><dt>متخصص</dt><dd>{{ $booking->staff?->name }}</dd></div>
                <div><dt>زمان</dt><dd>{{ PersianNumber::digits($startsAt->format('Y/m/d')) }} ساعت {{ PersianNumber::digits($startsAt->format('H:i')) }}</dd></div>
                <div><dt>مدت</dt><dd>{{ PersianNumber::digits($booking->durationMinutes()) }} دقیقه</dd></div>
                <div><dt>مبلغ</dt><dd>{{ PersianNumber::money($booking->price) }}</dd></div>
                <div><dt>وضعیت</dt><dd><span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span></dd></div>
                <div><dt>نام</dt><dd>{{ $booking->customer?->name }}</dd></div>
                <div><dt>آدرس</dt><dd>{{ $tenant->address ?: '—' }}</dd></div>
            </dl>

            @if ($canCancel)
                <form method="post"
                      action="{{ route('public.cancel', ['tenant' => $tenant->slug, 'code' => $booking->code]) }}"
                      style="margin-top: var(--s-6);">
                    @csrf
                    <button type="submit" class="btn btn--danger" onclick="return confirm('نوبت لغو شود؟')">
                        <x-icon name="x" class="icon icon--sm" />
                        لغو نوبت
                    </button>
                </form>
                <p class="muted small" style="margin-top: var(--s-3); margin-bottom: 0;">
                    لغو تا {{ PersianNumber::digits($tenant->cancellationWindowHours()) }} ساعت قبل از زمان نوبت امکان‌پذیر است.
                </p>
            @elseif ($booking->status->isBlocking())
                <p class="muted small" style="margin-top: var(--s-5);">
                    مهلت لغو آنلاین این نوبت گذشته است. برای تغییر با کسب‌وکار تماس بگیرید.
                </p>
            @endif
        </div>

        <p style="margin-top: var(--s-5);">
            <a class="btn btn--ghost" href="{{ route('public.booking', ['tenant' => $tenant->slug]) }}">
                <x-icon name="calendar" class="icon icon--sm" />
                رزرو نوبت دیگر
            </a>
        </p>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var button = document.querySelector('[data-copy]');
            var status = document.getElementById('copy-status');

            if (!button || !status) {
                return;
            }

            button.addEventListener('click', function () {
                var value = button.getAttribute('data-copy') || '';

                if (!navigator.clipboard) {
                    status.textContent = 'مرورگر از کپی خودکار پشتیبانی نمی‌کند؛ کد را دستی انتخاب کنید.';
                    return;
                }

                navigator.clipboard.writeText(value).then(function () {
                    status.textContent = 'کد پیگیری کپی شد.';
                }).catch(function () {
                    status.textContent = 'کپی انجام نشد؛ کد را دستی انتخاب کنید.';
                });
            });
        })();
    </script>
@endpush
