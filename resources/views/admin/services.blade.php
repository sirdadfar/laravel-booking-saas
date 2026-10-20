@use('App\Support\PersianNumber')

@extends('layouts.app')

@section('title', 'خدمات')

@section('content')
    <div class="stack">
        <div class="page__head">
            <div>
                <span class="eyebrow">کاتالوگ خدمات</span>
                <h1>خدمات</h1>
                <p class="muted">مدت هر خدمت و فاصلهٔ پاک‌سازی، مستقیماً روی زمان‌های آزاد اثر می‌گذارد.</p>
            </div>
            <span class="pill">
                <x-icon name="scissors" class="icon icon--sm" />
                {{ PersianNumber::grouped($services->count()) }} خدمت
            </span>
        </div>

        @include('admin.partials.nav')

        <section class="card reveal" aria-labelledby="services-title">
            <div class="card__head">
                <div>
                    <span class="eyebrow">فهرست</span>
                    <h2 class="card__title" id="services-title">خدمات فعلی</h2>
                </div>
                <span class="muted small">برای ویرایش، ردیف خدمت را باز کنید.</span>
            </div>

            @if ($services->isEmpty())
                <div class="empty">هنوز خدمتی ثبت نشده است.</div>
            @else
                <div class="table-wrap">
                    <table class="table table--services">
                        <caption class="sr-only">فهرست خدمات</caption>
                        <thead>
                        <tr>
                            <th scope="col">خدمت</th>
                            <th scope="col">مدت و بافر</th>
                            <th scope="col">قیمت</th>
                            <th scope="col">نوبت‌ها</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">ویرایش</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($services as $service)
                            <tr>
                                <td data-label="خدمت">
                                    <span class="cell-stack">
                                        <span>
                                            <span
                                                class="dot"
                                                style="background: {{ $service->color ?: '#0f766e' }}; vertical-align: middle; margin-inline-end: var(--s-2);"
                                                aria-hidden="true"
                                            ></span>
                                            <strong>{{ $service->name }}</strong>
                                        </span>
                                        <span class="dim small">
                                            {{ $service->staff->pluck('name')->join('، ') ?: 'متخصصی تعیین نشده' }}
                                        </span>
                                    </span>
                                </td>
                                <td data-label="مدت و بافر">
                                    <span class="cell-stack">
                                        <span class="num">{{ PersianNumber::digits($service->duration_minutes) }} دقیقه</span>
                                        <span class="dim small num">بافر {{ PersianNumber::digits($service->buffer_minutes) }} دقیقه</span>
                                    </span>
                                </td>
                                <td data-label="قیمت" class="num">
                                    {{ PersianNumber::grouped($service->price) }}
                                    <span class="dim small">تومان</span>
                                </td>
                                <td data-label="نوبت‌ها" class="num">{{ PersianNumber::grouped($service->bookings_count) }}</td>
                                <td data-label="وضعیت">
                                    <span class="badge {{ $service->is_active ? 'badge-success' : 'badge-muted' }}">
                                        {{ $service->is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </td>
                                <td data-label="ویرایش">
                                    <details class="inline-details">
                                        <summary>
                                            <x-icon name="pencil" class="icon icon--sm" />
                                            ویرایش
                                        </summary>
                                        <div class="details-body">
                                            <form method="post"
                                                  action="{{ route('admin.services.update', ['tenant' => $tenant->slug, 'service' => $service->id]) }}"
                                                  class="form form--compact">
                                                @csrf
                                                @method('put')

                                                @include('admin.partials.service-fields', ['service' => $service])

                                                <div>
                                                    <button type="submit" class="btn btn--gold btn--sm">
                                                        <x-icon name="check" class="icon icon--sm" />
                                                        ذخیره
                                                    </button>
                                                </div>
                                            </form>

                                            <form method="post"
                                                  action="{{ route('admin.services.destroy', ['tenant' => $tenant->slug, 'service' => $service->id]) }}"
                                                  onsubmit="return confirm('این خدمت حذف شود؟')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn--danger btn--sm">
                                                    <x-icon name="trash" class="icon icon--sm" />
                                                    حذف
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @include('partials.pagination', ['paginator' => $services])
            @endif
        </section>

        <section class="card card--gold reveal" aria-labelledby="add-service-title">
            <div class="card__head">
                <div>
                    <span class="eyebrow">خدمت تازه</span>
                    <h2 class="card__title" id="add-service-title">افزودن خدمت</h2>
                    <p class="muted small" style="margin: 0;">
                        مدت، بافر پاک‌سازی و متخصص‌های هر خدمت، ورودی موتور زمان‌های آزاد هستند.
                    </p>
                </div>
                <x-icon name="plus" class="icon icon--lg" />
            </div>

            <form method="post" action="{{ route('admin.services.store', ['tenant' => $tenant->slug]) }}" class="form">
                @csrf

                @include('admin.partials.service-fields', ['service' => null])

                <div>
                    <button type="submit" class="btn btn--gold">
                        <x-icon name="plus" class="icon icon--sm" />
                        افزودن
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
