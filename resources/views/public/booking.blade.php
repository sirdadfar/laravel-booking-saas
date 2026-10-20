@use('App\Support\PersianNumber')

@extends('layouts.app')

@section('title', 'رزرو نوبت')

@section('content')
    <div class="page__head">
        <div>
            <span class="eyebrow">رزرو آنلاین</span>
            <h1>{{ $tenant->name }}</h1>
            <p class="muted">
                <x-icon name="map-pin" class="icon icon--sm" />
                {{ $tenant->address ?: 'آدرس ثبت نشده' }}
                @if ($tenant->phone)
                    <span aria-hidden="true">·</span>
                    <span dir="ltr">{{ $tenant->phone }}</span>
                @endif
            </p>
        </div>
        <span class="pill">
            <x-icon name="clock" class="icon icon--sm" />
            منطقهٔ زمانی: <span dir="ltr">{{ $tenant->timezone() }}</span>
        </span>
    </div>

    <div class="booking-layout">
        <form
            id="booking-form"
            method="post"
            action="{{ route('public.bookings.store', ['tenant' => $tenant->slug]) }}"
            class="card form"
            novalidate
        >
            @csrf

            <div>
                <div class="step">
                    <span class="step__num" aria-hidden="true">۱</span>
                    <div>
                        <p class="step__title">خدمت و زمان</p>
                        <p class="step__hint">خدمت، متخصص و تاریخ را انتخاب کنید تا زمان‌های آزاد محاسبه شوند.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <div class="field">
                        <label for="service_id">خدمت</label>
                        <select id="service_id" name="service_id" class="select" required>
                            <option value="">انتخاب کنید…</option>
                            @foreach ($services as $service)
                                <option
                                    value="{{ $service->id }}"
                                    data-duration="{{ $service->duration_minutes }}"
                                    data-price="{{ $service->price }}"
                                    @selected(old('service_id') == $service->id)
                                >
                                    {{ $service->name }} ({{ PersianNumber::digits($service->duration_minutes) }} دقیقه)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="staff_id">متخصص</label>
                        <select id="staff_id" name="staff_id" class="select">
                            <option value="">هر متخصصی که آزاد باشد</option>
                            @foreach ($staff as $member)
                                <option value="{{ $member->id }}" @selected(old('staff_id') == $member->id)>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field__hint">اگر فرقی ندارد، خالی بگذارید.</span>
                    </div>

                    <div class="field">
                        <label for="date">تاریخ</label>
                        <input
                            type="date"
                            id="date"
                            name="date"
                            dir="ltr"
                            min="{{ $today }}"
                            max="{{ $horizonEnd }}"
                            value="{{ old('date', $today) }}"
                            required
                        >
                        <span class="field__hint">
                            تا <span dir="ltr">{{ PersianNumber::digits($horizonEnd) }}</span> قابل رزرو است.
                        </span>
                    </div>
                </div>
            </div>

            <hr class="divider" style="margin-block: var(--s-2);">

            <div>
                <div class="step">
                    <span class="step__num" aria-hidden="true">۲</span>
                    <div>
                        <p class="step__title">زمان‌های آزاد</p>
                        <p class="step__hint" id="service-hint" role="status">اول خدمت و تاریخ را انتخاب کنید.</p>
                    </div>
                </div>

                <div id="slots" class="slots" aria-live="polite" aria-busy="false"></div>
            </div>

            <input type="hidden" name="starts_at" id="starts_at" value="{{ old('starts_at') }}">

            <div id="customer-block" class="stack" @if (! old('starts_at')) hidden @endif>
                <hr class="divider" style="margin-block: var(--s-2);">

                <div class="step">
                    <span class="step__num" aria-hidden="true">۳</span>
                    <div>
                        <p class="step__title">اطلاعات شما</p>
                        <p class="step__hint">فقط نام و شمارهٔ همراه لازم است؛ پیامک تأیید برای همین شماره ارسال می‌شود.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <div class="field">
                        <label for="name">نام و نام خانوادگی</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="input"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="phone">شمارهٔ همراه</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="input"
                            value="{{ old('phone') }}"
                            dir="ltr"
                            inputmode="tel"
                            autocomplete="tel"
                            placeholder="09121234567"
                            required
                        >
                        <span class="field__hint">۱۱ رقم، با ۰۹ شروع شود.</span>
                    </div>

                    <div class="field">
                        <label for="email">ایمیل <span class="dim">(اختیاری)</span></label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="input"
                            value="{{ old('email') }}"
                            dir="ltr"
                            autocomplete="email"
                        >
                    </div>

                    <div class="field">
                        <label for="notes">توضیح <span class="dim">(اختیاری)</span></label>
                        <input
                            type="text"
                            id="notes"
                            name="notes"
                            class="input"
                            value="{{ old('notes') }}"
                            maxlength="500"
                        >
                    </div>
                </div>

                <p class="selected-slot muted small" id="selected-slot"></p>

                <button type="submit" class="btn btn--gold btn--block">
                    <x-icon name="check" class="icon icon--sm" />
                    ثبت نوبت
                </button>
            </div>
        </form>

        <aside class="card card--gold summary reveal" aria-labelledby="summary-title">
            <p class="eyebrow" id="summary-title">خلاصهٔ رزرو</p>

            <dl class="summary__rows">
                <div class="summary__row">
                    <dt>خدمت</dt>
                    <dd id="summary-service">—</dd>
                </div>
                <div class="summary__row">
                    <dt>مدت</dt>
                    <dd id="summary-duration">—</dd>
                </div>
                <div class="summary__row">
                    <dt>متخصص</dt>
                    <dd id="summary-staff">—</dd>
                </div>
                <div class="summary__row">
                    <dt>تاریخ</dt>
                    <dd id="summary-date">—</dd>
                </div>
                <div class="summary__row">
                    <dt>زمان</dt>
                    <dd id="summary-time">—</dd>
                </div>
                <div class="summary__row summary__total">
                    <dt>مبلغ</dt>
                    <dd id="summary-price">—</dd>
                </div>
            </dl>

            <p class="field__hint" style="margin-top: var(--s-4); margin-bottom: 0;">
                مبلغ در لحظهٔ ثبت از قیمت خدمت ذخیره می‌شود و با تغییر قیمت‌های بعدی عوض نمی‌شود.
            </p>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            var form = document.getElementById('booking-form');
            var service = document.getElementById('service_id');
            var staff = document.getElementById('staff_id');
            var date = document.getElementById('date');
            var slotsEl = document.getElementById('slots');
            var hint = document.getElementById('service-hint');
            var startsAt = document.getElementById('starts_at');
            var customerBlock = document.getElementById('customer-block');
            var selectedSlot = document.getElementById('selected-slot');
            var slotsUrl = @json(route('public.slots', ['tenant' => $tenant->slug]));

            // app.js is deferred, so this inline block may run before it. Use the
            // shared helper when it exists and keep a local fallback otherwise.
            var toFa = (window.Nobatyar && window.Nobatyar.toFa) || function (value) {
                return String(value).replace(/\d/g, function (digit) {
                    return '۰۱۲۳۴۵۶۷۸۹'[Number(digit)];
                });
            };

            var summary = {
                service: document.getElementById('summary-service'),
                duration: document.getElementById('summary-duration'),
                staff: document.getElementById('summary-staff'),
                date: document.getElementById('summary-date'),
                time: document.getElementById('summary-time'),
                price: document.getElementById('summary-price')
            };

            var pending = null;

            function faDate(value) {
                if (!value) {
                    return '—';
                }
                var parts = value.split('-');
                return parts.length === 3 ? toFa(parts[0] + '/' + parts[1] + '/' + parts[2]) : toFa(value);
            }

            function faPrice(value) {
                if (value === null || value === undefined || value === '') {
                    return '—';
                }
                return toFa(Number(value).toLocaleString('en-US')) + ' تومان';
            }

            function selectedOption(select) {
                return select.options[select.selectedIndex] || null;
            }

            function syncSummary() {
                var option = selectedOption(service);
                var name = option && option.value ? option.textContent.trim() : '';
                summary.service.textContent = name || '—';

                var duration = option && option.dataset ? Number(option.dataset.duration || 0) : 0;
                summary.duration.textContent = duration ? toFa(duration) + ' دقیقه' : '—';

                var price = option && option.dataset ? option.dataset.price : '';
                summary.price.textContent = price !== '' ? faPrice(price) : '—';

                var member = selectedOption(staff);
                summary.staff.textContent = member && member.value ? member.textContent.trim() : 'هر متخصص آزاد';

                summary.date.textContent = faDate(date.value);
            }

            function resetSelection() {
                startsAt.value = '';
                customerBlock.hidden = true;
                selectedSlot.textContent = '';
                summary.time.textContent = '—';
            }

            function setHint(message) {
                hint.textContent = message;
            }

            function skeletons(count) {
                slotsEl.innerHTML = '';
                for (var i = 0; i < count; i++) {
                    var block = document.createElement('div');
                    block.className = 'slot-skeleton';
                    block.setAttribute('aria-hidden', 'true');
                    slotsEl.appendChild(block);
                }
            }

            function emptyState(message) {
                var box = document.createElement('div');
                box.className = 'empty';
                box.textContent = message;
                slotsEl.appendChild(box);
            }

            function renderSlots(payload) {
                slotsEl.innerHTML = '';

                if (!payload.slots || payload.slots.length === 0) {
                    setHint('برای این روز زمان آزادی نیست. روز دیگری را امتحان کنید.');
                    emptyState('زمان آزادی برای این ترکیب خدمت و متخصص پیدا نشد.');
                    return;
                }

                setHint(toFa(payload.slots.length) + ' زمان آزاد در ' + faDate(payload.date));

                payload.slots.forEach(function (slot) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'slot';
                    button.setAttribute('aria-pressed', 'false');
                    button.textContent = toFa(slot.time) + ' تا ' + toFa(slot.end_time);

                    button.addEventListener('click', function () {
                        Array.prototype.forEach.call(slotsEl.querySelectorAll('.slot'), function (element) {
                            element.classList.remove('is-selected');
                            element.setAttribute('aria-pressed', 'false');
                        });

                        button.classList.add('is-selected');
                        button.setAttribute('aria-pressed', 'true');

                        startsAt.value = payload.date + ' ' + slot.time;
                        selectedSlot.textContent = 'زمان انتخابی: ' + faDate(payload.date) + ' ساعت ' + toFa(slot.time);
                        summary.time.textContent = toFa(slot.time) + ' تا ' + toFa(slot.end_time);
                        customerBlock.hidden = false;

                        var nameField = document.getElementById('name');
                        if (nameField) {
                            nameField.focus({ preventScroll: true });
                        }
                    });

                    slotsEl.appendChild(button);
                });
            }

            async function loadSlots() {
                resetSelection();
                syncSummary();

                if (pending) {
                    pending.abort();
                    pending = null;
                }

                if (!service.value || !date.value) {
                    slotsEl.innerHTML = '';
                    setHint('اول خدمت و تاریخ را انتخاب کنید.');
                    slotsEl.setAttribute('aria-busy', 'false');
                    return;
                }

                setHint('در حال دریافت زمان‌ها…');
                skeletons(6);
                slotsEl.setAttribute('aria-busy', 'true');

                var params = new URLSearchParams({ service_id: service.value, date: date.value });
                if (staff.value) {
                    params.set('staff_id', staff.value);
                }

                pending = new AbortController();

                try {
                    var response = await fetch(slotsUrl + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json' },
                        signal: pending.signal
                    });

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    renderSlots(await response.json());
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    slotsEl.innerHTML = '';
                    setHint('دریافت زمان‌ها ناموفق بود. صفحه را دوباره بارگذاری کنید.');
                } finally {
                    slotsEl.setAttribute('aria-busy', 'false');
                    pending = null;
                }
            }

            [service, staff, date].forEach(function (element) {
                element.addEventListener('change', loadSlots);
            });

            if (startsAt.value) {
                var stamp = startsAt.value.split(' ');
                if (stamp.length === 2) {
                    selectedSlot.textContent = 'زمان انتخابی: ' + faDate(stamp[0]) + ' ساعت ' + toFa(stamp[1]);
                    summary.time.textContent = toFa(stamp[1]);
                }
            }

            syncSummary();

            if (service.value && date.value) {
                loadSlots();
            }
        })();
    </script>
@endpush
