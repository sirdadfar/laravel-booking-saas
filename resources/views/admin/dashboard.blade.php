@extends('layouts.app')

@section('title', 'داشبورد '.$tenant->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>داشبورد</h1>
            <p class="muted">
                امروز {{ $now->format('Y/m/d') }} — {{ \App\Support\IranianWeek::dayName($now) }}
            </p>
        </div>
        <a class="button" href="{{ route('public.booking', ['tenant' => $tenant->slug]) }}" target="_blank" rel="noopener">
            مشاهدهٔ صفحهٔ رزرو
        </a>
    </div>

    @include('admin.partials.nav')

    <div class="stat-grid">
        <div class="stat">
            <span class="stat-label">نوبت‌های امروز</span>
            <strong>{{ number_format($todayBookings->count()) }}</strong>
        </div>
        <div class="stat">
            <span class="stat-label">نوبت‌های این هفته</span>
            <strong>{{ number_format($weekCount) }}</strong>
        </div>
        <div class="stat">
            <span class="stat-label">درآمد نوبت‌های انجام‌شدهٔ این ماه</span>
            <strong>{{ number_format($monthRevenue) }} <small>تومان</small></strong>
        </div>
        <div class="stat">
            <span class="stat-label">در انتظار تأیید (این ماه)</span>
            <strong>{{ number_format((int) ($statusCounts['pending'] ?? 0)) }}</strong>
        </div>
    </div>

    <section class="panel">
        <h2>برنامهٔ امروز</h2>

        @if ($todayBookings->isEmpty())
            <p class="muted">امروز نوبتی ثبت نشده است.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>ساعت</th>
                        <th>خدمت</th>
                        <th>متخصص</th>
                        <th>مشتری</th>
                        <th>وضعیت</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($todayBookings as $booking)
                        <tr>
                            <td dir="ltr">{{ $booking->startsAtIn($tenant->timezone())->format('H:i') }}</td>
                            <td>{{ $booking->service?->name }}</td>
                            <td>{{ $booking->staff?->name }}</td>
                            <td>
                                {{ $booking->customer?->name }}
                                <span class="muted small" dir="ltr">{{ $booking->customer?->phone }}</span>
                            </td>
                            <td><span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <h2>نوبت‌های پیش‌رو</h2>

        @if ($upcoming->isEmpty())
            <p class="muted">نوبت آینده‌ای ثبت نشده است.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>ساعت</th>
                        <th>خدمت</th>
                        <th>متخصص</th>
                        <th>مشتری</th>
                        <th>مبلغ</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($upcoming as $booking)
                        <tr>
                            <td>{{ $booking->startsAtIn($tenant->timezone())->format('Y/m/d') }}</td>
                            <td dir="ltr">{{ $booking->startsAtIn($tenant->timezone())->format('H:i') }}</td>
                            <td>{{ $booking->service?->name }}</td>
                            <td>{{ $booking->staff?->name }}</td>
                            <td>{{ $booking->customer?->name }}</td>
                            <td>{{ number_format($booking->price) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
