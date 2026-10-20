@use('App\Support\PersianNumber')

@extends('layouts.app')

@section('title', 'نوبت‌ها')

@section('content')
    <div class="stack">
        <div class="page__head">
            <div>
                <span class="eyebrow">عملیات روزانه</span>
                <h1>نوبت‌ها</h1>
                <p class="muted">فهرست نوبت‌های یک روز. برای دیدن روز دیگر تاریخ را تغییر دهید.</p>
            </div>
            <span class="pill">
                <x-icon name="calendar" class="icon icon--sm" />
                <span dir="ltr">{{ PersianNumber::digits($date) }}</span>
            </span>
        </div>

        @include('admin.partials.nav')

        <form
            method="get"
            action="{{ route('admin.bookings', ['tenant' => $tenant->slug]) }}"
            class="card form form--compact"
            role="search"
            aria-label="فیلتر نوبت‌ها"
        >
            <div class="field-grid">
                <div class="field">
                    <label for="date">تاریخ</label>
                    <input type="date" id="date" name="date" class="input" dir="ltr" value="{{ $date }}">
                </div>

                <div class="field">
                    <label for="status">وضعیت</label>
                    <select id="status" name="status" class="select">
                        <option value="">همه</option>
                        @foreach ($statuses as $case)
                            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn--gold">
                    <x-icon name="list" class="icon icon--sm" />
                    نمایش
                </button>
            </div>
        </form>

        <section class="card reveal" aria-labelledby="results-title">
            <div class="card__head">
                <div>
                    <span class="eyebrow">نتیجهٔ فیلتر</span>
                    <h2 class="card__title" id="results-title">فهرست نوبت‌ها</h2>
                </div>
                <span class="pill">
                    <x-icon name="list" class="icon icon--sm" />
                    {{ PersianNumber::grouped($bookings->count()) }} نوبت
                </span>
            </div>

            @if ($bookings->isEmpty())
                <div class="empty">در این روز با این فیلتر نوبتی پیدا نشد.</div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <caption class="sr-only">نوبت‌های روز {{ PersianNumber::digits($date) }}</caption>
                        <thead>
                        <tr>
                            <th scope="col">زمان</th>
                            <th scope="col">خدمت</th>
                            <th scope="col">مشتری</th>
                            <th scope="col">مبلغ</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">تغییر وضعیت</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                <td data-label="زمان">
                                    <span class="cell-stack">
                                        <span class="num" dir="ltr">
                                            {{ PersianNumber::digits($booking->startsAtIn($tenant->timezone())->format('H:i')) }}
                                            –
                                            {{ PersianNumber::digits($booking->endsAtIn($tenant->timezone())->format('H:i')) }}
                                        </span>
                                        <code class="small" dir="ltr">{{ $booking->code }}</code>
                                    </span>
                                </td>
                                <td data-label="خدمت">
                                    <span class="cell-stack">
                                        <span>{{ $booking->service?->name }}</span>
                                        <span class="dim small">{{ $booking->staff?->name }}</span>
                                    </span>
                                </td>
                                <td data-label="مشتری">
                                    <span class="cell-stack">
                                        <span>{{ $booking->customer?->name }}</span>
                                        <span class="dim small" dir="ltr">{{ $booking->customer?->phone }}</span>
                                    </span>
                                </td>
                                <td data-label="مبلغ" class="num">
                                    {{ PersianNumber::grouped($booking->price) }}
                                    <span class="dim small">تومان</span>
                                </td>
                                <td data-label="وضعیت">
                                    <span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span>
                                </td>
                                <td data-label="تغییر وضعیت">
                                    @php
                                        $targets = array_filter(
                                            \App\Enums\BookingStatus::cases(),
                                            fn ($case) => $booking->status->canTransitionTo($case),
                                        );
                                    @endphp

                                    <div class="chip-row">
                                        @forelse ($targets as $target)
                                            <form method="post"
                                                  action="{{ route('admin.bookings.status', ['tenant' => $tenant->slug, 'booking' => $booking->id]) }}"
                                                  class="inline-form">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $target->value }}">
                                                <button type="submit" class="btn btn--ghost btn--sm">{{ $target->label() }}</button>
                                            </form>
                                        @empty
                                            <span class="muted small">پایان‌یافته</span>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @include('partials.pagination', ['paginator' => $bookings])
            @endif
        </section>
    </div>
@endsection
