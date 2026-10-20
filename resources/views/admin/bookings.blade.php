@extends('layouts.app')

@section('title', 'نوبت‌ها — '.$tenant->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>نوبت‌ها</h1>
            <p class="muted">فهرست نوبت‌های یک روز. برای دیدن روز دیگر تاریخ را تغییر دهید.</p>
        </div>
    </div>

    @include('admin.partials.nav')

    <form method="get" action="{{ route('admin.bookings', ['tenant' => $tenant->slug]) }}" class="panel filter-bar">
        <div class="field">
            <label for="date">تاریخ</label>
            <input type="date" id="date" name="date" dir="ltr" value="{{ $date }}">
        </div>

        <div class="field">
            <label for="status">وضعیت</label>
            <select id="status" name="status">
                <option value="">همه</option>
                @foreach ($statuses as $case)
                    <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="button">نمایش</button>
    </form>

    <section class="panel">
        @if ($bookings->isEmpty())
            <p class="muted">در این روز با این فیلتر نوبتی پیدا نشد.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>ساعت</th>
                        <th>کد</th>
                        <th>خدمت</th>
                        <th>متخصص</th>
                        <th>مشتری</th>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                        <th>تغییر وضعیت</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td dir="ltr">
                                {{ $booking->startsAtIn($tenant->timezone())->format('H:i') }}
                                –
                                {{ $booking->endsAtIn($tenant->timezone())->format('H:i') }}
                            </td>
                            <td dir="ltr"><code>{{ $booking->code }}</code></td>
                            <td>{{ $booking->service?->name }}</td>
                            <td>{{ $booking->staff?->name }}</td>
                            <td>
                                {{ $booking->customer?->name }}
                                <span class="muted small" dir="ltr">{{ $booking->customer?->phone }}</span>
                            </td>
                            <td>{{ number_format($booking->price) }}</td>
                            <td><span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span></td>
                            <td>
                                @php
                                    $targets = array_filter(
                                        \App\Enums\BookingStatus::cases(),
                                        fn ($case) => $booking->status->canTransitionTo($case),
                                    );
                                @endphp

                                @forelse ($targets as $target)
                                    <form method="post"
                                          action="{{ route('admin.bookings.status', ['tenant' => $tenant->slug, 'booking' => $booking->id]) }}"
                                          class="inline-form">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $target->value }}">
                                        <button type="submit" class="button button-small button-ghost">{{ $target->label() }}</button>
                                    </form>
                                @empty
                                    <span class="muted small">پایان‌یافته</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
