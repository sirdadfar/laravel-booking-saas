@use('App\Support\PersianNumber')

@extends('layouts.app')

@section('title', 'کسب‌وکارها')

@section('content')
    <div class="stack">
        <div class="page__head">
            <div>
                <span class="eyebrow">پنل پلتفرم</span>
                <h1>کسب‌وکارها</h1>
                <p class="muted">نمای کلی مستأجرها. هر مستأجر دادهٔ کاملاً جدا دارد.</p>
            </div>
            <span class="pill">
                <x-icon name="grid" class="icon icon--sm" />
                {{ PersianNumber::grouped($tenants->count()) }} کسب‌وکار
            </span>
        </div>

        <section class="card reveal" aria-labelledby="tenants-title">
            <div class="card__head">
                <div>
                    <span class="eyebrow">فهرست مستأجرها</span>
                    <h2 class="card__title" id="tenants-title">همهٔ کسب‌وکارها</h2>
                </div>
                <span class="muted small">شمارنده‌ها از دادهٔ همین لحظه خوانده می‌شوند.</span>
            </div>

            @if ($tenants->isEmpty())
                <div class="empty">هنوز کسب‌وکاری ثبت نشده است.</div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <caption class="sr-only">فهرست کسب‌وکارهای پلتفرم</caption>
                        <thead>
                        <tr>
                            <th scope="col">کسب‌وکار</th>
                            <th scope="col">منطقهٔ زمانی</th>
                            <th scope="col">خدمات</th>
                            <th scope="col">تیم</th>
                            <th scope="col">نوبت‌ها</th>
                            <th scope="col">کاربران</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">صفحهٔ رزرو</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($tenants as $business)
                            <tr>
                                <td data-label="کسب‌وکار">
                                    <span class="cell-stack">
                                        <strong>{{ $business->name }}</strong>
                                        <span class="dim small" dir="ltr">/{{ $business->slug }}</span>
                                    </span>
                                </td>
                                <td data-label="منطقهٔ زمانی" class="num" dir="ltr">{{ $business->timezone() }}</td>
                                <td data-label="خدمات" class="num">{{ PersianNumber::grouped($business->services_count) }}</td>
                                <td data-label="تیم" class="num">{{ PersianNumber::grouped($business->staff_count) }}</td>
                                <td data-label="نوبت‌ها" class="num">{{ PersianNumber::grouped($business->bookings_count) }}</td>
                                <td data-label="کاربران" class="num">{{ PersianNumber::grouped($business->users_count) }}</td>
                                <td data-label="وضعیت">
                                    <span class="badge {{ $business->isActive() ? 'badge-success' : 'badge-muted' }}">
                                        {{ $business->isActive() ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </td>
                                <td data-label="صفحهٔ رزرو">
                                    <a class="btn btn--ghost btn--sm"
                                       href="{{ route('public.booking', ['tenant' => $business->slug]) }}">
                                        <x-icon name="external" class="icon icon--sm" />
                                        صفحهٔ رزرو
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @include('partials.pagination', ['paginator' => $tenants])
            @endif
        </section>
    </div>
@endsection
