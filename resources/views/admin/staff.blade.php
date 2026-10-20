@extends('layouts.app')

@section('title', 'تیم — '.$tenant->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>تیم و ساعات کاری</h1>
            <p class="muted">
                هر عضو می‌تواند چند بازهٔ کاری در یک روز داشته باشد؛ بازه‌ها را با کاما جدا کنید:
                <code dir="ltr">09:00-13:00, 16:00-20:00</code>
            </p>
        </div>
    </div>

    @include('admin.partials.nav')

    <section class="panel">
        <h2>اعضای تیم</h2>

        @if ($staff->isEmpty())
            <p class="muted">هنوز عضوی ثبت نشده است.</p>
        @else
            <div class="staff-list">
                @foreach ($staff as $member)
                    <article class="staff-card">
                        <header class="staff-card-head">
                            <div>
                                <strong>{{ $member->name }}</strong>
                                <span class="muted small" dir="ltr">{{ $member->phone ?: '—' }}</span>
                            </div>
                            <span class="badge {{ $member->is_active ? 'badge-success' : 'badge-muted' }}">
                                {{ $member->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </header>

                        <p class="muted small">
                            خدمات: {{ $member->services->pluck('name')->join('، ') ?: '—' }}
                        </p>

                        <div class="shift-chips">
                            @foreach (\App\Support\IranianWeek::LABELS as $weekday => $label)
                                @php
                                    $windows = $member->schedules
                                        ->where('weekday', $weekday)
                                        ->map(fn ($schedule) => $schedule->start_time.'-'.$schedule->end_time);
                                @endphp
                                <span class="chip {{ $windows->isEmpty() ? 'chip-off' : '' }}">
                                    {{ $label }}:
                                    <span dir="ltr">{{ $windows->isEmpty() ? 'تعطیل' : $windows->join(', ') }}</span>
                                </span>
                            @endforeach
                        </div>

                        <details class="inline-details">
                            <summary class="button button-small button-ghost">ویرایش</summary>
                            <div class="details-body">
                                <form method="post"
                                      action="{{ route('admin.staff.update', ['tenant' => $tenant->slug, 'staff' => $member->id]) }}"
                                      class="form compact-form">
                                    @csrf
                                    @method('put')

                                    @include('admin.partials.staff-fields', ['member' => $member])

                                    <button type="submit" class="button">ذخیره</button>
                                </form>

                                <form method="post"
                                      action="{{ route('admin.staff.destroy', ['tenant' => $tenant->slug, 'staff' => $member->id]) }}"
                                      onsubmit="return confirm('این عضو حذف شود؟')">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="button button-small button-danger">حذف</button>
                                </form>
                            </div>
                        </details>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="panel">
        <h2>افزودن عضو</h2>

        <form method="post" action="{{ route('admin.staff.store', ['tenant' => $tenant->slug]) }}" class="form">
            @csrf

            @include('admin.partials.staff-fields', ['member' => null])

            <button type="submit" class="button">افزودن</button>
        </form>
    </section>
@endsection
