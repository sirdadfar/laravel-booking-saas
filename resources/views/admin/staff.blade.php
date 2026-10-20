@use('App\Support\PersianNumber')

@extends('layouts.app')

@section('title', 'تیم')

@section('content')
    <div class="stack">
        <div class="page__head">
            <div>
                <span class="eyebrow">تیم و شیفت‌ها</span>
                <h1>تیم و ساعات کاری</h1>
                <p class="muted">
                    هر عضو می‌تواند چند بازهٔ کاری در یک روز داشته باشد؛ بازه‌ها را با کاما جدا کنید:
                    <code dir="ltr">09:00-13:00, 16:00-20:00</code>
                </p>
            </div>
            <span class="pill">
                <x-icon name="users" class="icon icon--sm" />
                {{ PersianNumber::grouped($staff->count()) }} عضو
            </span>
        </div>

        @include('admin.partials.nav')

        <section class="card reveal" aria-labelledby="staff-title">
            <div class="card__head">
                <div>
                    <span class="eyebrow">فهرست</span>
                    <h2 class="card__title" id="staff-title">اعضای تیم</h2>
                </div>
                <span class="muted small">چیپ‌های کم‌رنگ، روزهای تعطیل هر عضو هستند.</span>
            </div>

            @if ($staff->isEmpty())
                <div class="empty">هنوز عضوی ثبت نشده است.</div>
            @else
                <div class="staff-list">
                    @foreach ($staff as $member)
                        <article class="staff-card reveal">
                            <header class="staff-card__head">
                                <div class="staff-card__identity">
                                    <span class="avatar" aria-hidden="true">
                                        {{ \Illuminate\Support\Str::substr($member->name, 0, 1) }}
                                    </span>
                                    <div class="staff-card__name">
                                        <strong>{{ $member->name }}</strong>
                                        <span class="dim small" dir="ltr">
                                            {{ $member->phone ?: 'بدون شمارهٔ تماس' }}
                                        </span>
                                    </div>
                                </div>
                                <span class="badge {{ $member->is_active ? 'badge-success' : 'badge-muted' }}">
                                    {{ $member->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </header>

                            <div>
                                <div class="field__label" style="margin-bottom: var(--s-2);">خدمات قابل ارائه</div>
                                <div class="chip-row">
                                    @forelse ($member->services as $service)
                                        <span class="chip">
                                            <x-icon name="scissors" class="icon icon--sm" />
                                            {{ $service->name }}
                                        </span>
                                    @empty
                                        <span class="muted small">هنوز خدمتی به این عضو تخصیص نداده‌اید.</span>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <div class="field__label" style="margin-bottom: var(--s-2);">ساعات کاری هفتگی</div>
                                <div class="chip-row">
                                    @foreach (\App\Support\IranianWeek::LABELS as $weekday => $label)
                                        @php
                                            $windows = $member->schedules
                                                ->where('weekday', $weekday)
                                                ->map(fn ($schedule) => $schedule->start_time.'-'.$schedule->end_time);
                                        @endphp
                                        <span @class(['chip', 'chip--off' => $windows->isEmpty()])>
                                            <b>{{ $label }}</b>
                                            <span dir="ltr">{{ $windows->isEmpty() ? 'تعطیل' : $windows->join(', ') }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <details class="inline-details">
                                <summary>
                                    <x-icon name="pencil" class="icon icon--sm" />
                                    ویرایش
                                </summary>
                                <div class="details-body">
                                    <form method="post"
                                          action="{{ route('admin.staff.update', ['tenant' => $tenant->slug, 'staff' => $member->id]) }}"
                                          class="form form--compact">
                                        @csrf
                                        @method('put')

                                        @include('admin.partials.staff-fields', ['member' => $member])

                                        <div>
                                            <button type="submit" class="btn btn--gold btn--sm">
                                                <x-icon name="check" class="icon icon--sm" />
                                                ذخیره
                                            </button>
                                        </div>
                                    </form>

                                    <form method="post"
                                          action="{{ route('admin.staff.destroy', ['tenant' => $tenant->slug, 'staff' => $member->id]) }}"
                                          onsubmit="return confirm('این عضو حذف شود؟')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn--danger btn--sm">
                                            <x-icon name="trash" class="icon icon--sm" />
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $staff])
            @endif
        </section>

        <section class="card card--gold reveal" aria-labelledby="add-staff-title">
            <div class="card__head">
                <div>
                    <span class="eyebrow">عضو تازه</span>
                    <h2 class="card__title" id="add-staff-title">افزودن عضو</h2>
                    <p class="muted small" style="margin: 0;">
                        خدمات قابل ارائه و شیفت‌های هفتگی را از همان ابتدا مشخص کنید.
                    </p>
                </div>
                <x-icon name="user" class="icon icon--lg" />
            </div>

            <form method="post" action="{{ route('admin.staff.store', ['tenant' => $tenant->slug]) }}" class="form">
                @csrf

                @include('admin.partials.staff-fields', ['member' => null])

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
