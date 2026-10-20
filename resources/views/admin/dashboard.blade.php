@use('App\Support\PersianNumber')

@extends('layouts.app')

@section('title', 'داشبورد')

@section('content')
    <div class="stack">
        <div class="page__head">
            <div>
                <span class="eyebrow">نمای کلی کسب‌وکار</span>
                <h1>داشبورد</h1>
                <p class="muted">
                    <x-icon name="calendar" class="icon icon--sm" />
                    امروز {{ PersianNumber::digits($now->format('Y/m/d')) }} — {{ \App\Support\IranianWeek::dayName($now) }}
                </p>
            </div>
            <a
                class="btn btn--ghost"
                href="{{ route('public.booking', ['tenant' => $tenant->slug]) }}"
                target="_blank"
                rel="noopener"
            >
                <x-icon name="external" class="icon icon--sm" />
                مشاهدهٔ صفحهٔ رزرو
            </a>
        </div>

        @include('admin.partials.nav')

        <div class="stats">
            <div class="stat reveal">
                <span class="stat__label">
                    <x-icon name="calendar" class="icon icon--sm" />
                    نوبت‌های امروز
                </span>
                <strong class="stat__value">{{ PersianNumber::grouped($todayBookings->count()) }}</strong>
            </div>

            <div class="stat reveal">
                <span class="stat__label">
                    <x-icon name="chart" class="icon icon--sm" />
                    نوبت‌های این هفته
                </span>
                <strong class="stat__value">{{ PersianNumber::grouped($weekCount) }}</strong>
            </div>

            <div class="stat stat--gold reveal">
                <span class="stat__label">
                    <x-icon name="wallet" class="icon icon--sm" />
                    درآمد نوبت‌های انجام‌شدهٔ این ماه
                </span>
                <strong class="stat__value">{{ PersianNumber::grouped($monthRevenue) }}</strong>
                <span class="stat__unit">تومان</span>
            </div>

            <div class="stat reveal">
                <span class="stat__label">
                    <x-icon name="clock" class="icon icon--sm" />
                    در انتظار تأیید (این ماه)
                </span>
                <strong class="stat__value">{{ PersianNumber::grouped((int) ($statusCounts['pending'] ?? 0)) }}</strong>
            </div>
        </div>

        <section class="card reveal" aria-labelledby="today-title">
            <div class="card__head">
                <div>
                    <span class="eyebrow">برنامهٔ روز</span>
                    <h2 class="card__title" id="today-title">برنامهٔ امروز</h2>
                </div>
                <span class="pill">
                    <x-icon name="clock" class="icon icon--sm" />
                    {{ PersianNumber::grouped($todayBookings->count()) }} نوبت
                </span>
            </div>

            @if ($todayBookings->isEmpty())
                <div class="empty">امروز نوبتی ثبت نشده است.</div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <caption class="sr-only">نوبت‌های امروز</caption>
                        <thead>
                        <tr>
                            <th scope="col">ساعت</th>
                            <th scope="col">خدمت</th>
                            <th scope="col">متخصص</th>
                            <th scope="col">مشتری</th>
                            <th scope="col">وضعیت</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($todayBookings as $booking)
                            <tr>
                                <td data-label="ساعت" class="num" dir="ltr">
                                    {{ PersianNumber::digits($booking->startsAtIn($tenant->timezone())->format('H:i')) }}
                                </td>
                                <td data-label="خدمت">{{ $booking->service?->name }}</td>
                                <td data-label="متخصص">{{ $booking->staff?->name }}</td>
                                <td data-label="مشتری">
                                    <span class="cell-stack">
                                        <span>{{ $booking->customer?->name }}</span>
                                        <span class="dim small" dir="ltr">{{ $booking->customer?->phone }}</span>
                                    </span>
                                </td>
                                <td data-label="وضعیت">
                                    <span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card reveal" aria-labelledby="upcoming-title">
            <div class="card__head">
                <div>
                    <span class="eyebrow">ده نوبت بعدی</span>
                    <h2 class="card__title" id="upcoming-title">نوبت‌های پیش‌رو</h2>
                </div>
                <a class="btn btn--ghost btn--sm" href="{{ route('admin.bookings', ['tenant' => $tenant->slug]) }}">
                    همهٔ نوبت‌ها
                    <x-icon name="arrow-left" class="icon icon--sm" />
                </a>
            </div>

            @if ($upcoming->isEmpty())
                <div class="empty">نوبت آینده‌ای ثبت نشده است.</div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <caption class="sr-only">نوبت‌های پیش‌رو</caption>
                        <thead>
                        <tr>
                            <th scope="col">تاریخ</th>
                            <th scope="col">ساعت</th>
                            <th scope="col">خدمت</th>
                            <th scope="col">متخصص</th>
                            <th scope="col">مشتری</th>
                            <th scope="col">مبلغ</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($upcoming as $booking)
                            <tr>
                                <td data-label="تاریخ" class="num">
                                    {{ PersianNumber::digits($booking->startsAtIn($tenant->timezone())->format('Y/m/d')) }}
                                </td>
                                <td data-label="ساعت" class="num" dir="ltr">
                                    {{ PersianNumber::digits($booking->startsAtIn($tenant->timezone())->format('H:i')) }}
                                </td>
                                <td data-label="خدمت">{{ $booking->service?->name }}</td>
                                <td data-label="متخصص">{{ $booking->staff?->name }}</td>
                                <td data-label="مشتری">{{ $booking->customer?->name }}</td>
                                <td data-label="مبلغ" class="num">
                                    {{ PersianNumber::grouped($booking->price) }}
                                    <span class="dim small">تومان</span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
