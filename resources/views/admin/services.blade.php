@extends('layouts.app')

@section('title', 'خدمات — '.$tenant->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>خدمات</h1>
            <p class="muted">مدت هر خدمت و فاصلهٔ پاک‌سازی، مستقیماً روی زمان‌های آزاد اثر می‌گذارد.</p>
        </div>
    </div>

    @include('admin.partials.nav')

    <section class="panel">
        <h2>خدمات فعلی</h2>

        @if ($services->isEmpty())
            <p class="muted">هنوز خدمتی ثبت نشده است.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>خدمت</th>
                        <th>مدت</th>
                        <th>بافر</th>
                        <th>قیمت</th>
                        <th>متخصص‌ها</th>
                        <th>نوبت‌ها</th>
                        <th>وضعیت</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($services as $service)
                        <tr>
                            <td>
                                <span class="color-dot" style="background: {{ $service->color ?: '#0f766e' }}"></span>
                                {{ $service->name }}
                            </td>
                            <td>{{ $service->duration_minutes }} دقیقه</td>
                            <td>{{ $service->buffer_minutes }} دقیقه</td>
                            <td>{{ number_format($service->price) }} تومان</td>
                            <td>{{ $service->staff->pluck('name')->join('، ') ?: '—' }}</td>
                            <td>{{ $service->bookings_count }}</td>
                            <td>
                                <span class="badge {{ $service->is_active ? 'badge-success' : 'badge-muted' }}">
                                    {{ $service->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td>
                                <details class="inline-details">
                                    <summary class="button button-small button-ghost">ویرایش</summary>
                                    <div class="details-body">
                                        <form method="post"
                                              action="{{ route('admin.services.update', ['tenant' => $tenant->slug, 'service' => $service->id]) }}"
                                              class="form compact-form">
                                            @csrf
                                            @method('put')

                                            @include('admin.partials.service-fields', ['service' => $service])

                                            <button type="submit" class="button">ذخیره</button>
                                        </form>

                                        <form method="post"
                                              action="{{ route('admin.services.destroy', ['tenant' => $tenant->slug, 'service' => $service->id]) }}"
                                              onsubmit="return confirm('این خدمت حذف شود؟')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="button button-small button-danger">حذف</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel">
        <h2>افزودن خدمت</h2>

        <form method="post" action="{{ route('admin.services.store', ['tenant' => $tenant->slug]) }}" class="form">
            @csrf

            @include('admin.partials.service-fields', ['service' => null])

            <button type="submit" class="button">افزودن</button>
        </form>
    </section>
@endsection
