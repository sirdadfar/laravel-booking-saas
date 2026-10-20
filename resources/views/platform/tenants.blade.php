@extends('layouts.app')

@section('title', 'کسب‌وکارها — پنل پلتفرم')

@section('content')
    <div class="page-head">
        <div>
            <h1>کسب‌وکارها</h1>
            <p class="muted">نمای کلی مستأجرها. هر مستأجر دادهٔ کاملاً جدا دارد.</p>
        </div>
    </div>

    <section class="panel">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>نام</th>
                    <th>نشانی</th>
                    <th>منطقهٔ زمانی</th>
                    <th>خدمات</th>
                    <th>تیم</th>
                    <th>نوبت‌ها</th>
                    <th>کاربران</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($tenants as $tenant)
                    <tr>
                        <td>{{ $tenant->name }}</td>
                        <td dir="ltr">/{{ $tenant->slug }}</td>
                        <td dir="ltr">{{ $tenant->timezone() }}</td>
                        <td>{{ $tenant->services_count }}</td>
                        <td>{{ $tenant->staff_count }}</td>
                        <td>{{ $tenant->bookings_count }}</td>
                        <td>{{ $tenant->users_count }}</td>
                        <td>
                            <span class="badge {{ $tenant->isActive() ? 'badge-success' : 'badge-muted' }}">
                                {{ $tenant->isActive() ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td>
                            <a class="button button-small button-ghost"
                               href="{{ route('public.booking', ['tenant' => $tenant->slug]) }}">
                                صفحهٔ رزرو
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
