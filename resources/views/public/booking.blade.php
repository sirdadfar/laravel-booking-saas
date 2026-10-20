@extends('layouts.app')

@section('title', 'رزرو نوبت — '.$tenant->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $tenant->name }}</h1>
            <p class="muted">
                {{ $tenant->address ?: 'آدرس ثبت نشده' }}
                @if ($tenant->phone)
                    · <span dir="ltr">{{ $tenant->phone }}</span>
                @endif
            </p>
        </div>
        <span class="pill">منطقهٔ زمانی: {{ $tenant->timezone() }}</span>
    </div>

    <form id="booking-form" method="post" action="{{ route('public.bookings.store', ['tenant' => $tenant->slug]) }}" class="panel">
        @csrf

        <div class="grid-3">
            <div class="field">
                <label for="service_id">خدمت</label>
                <select id="service_id" name="service_id" required>
                    <option value="">انتخاب کنید…</option>
                    @foreach ($services as $service)
                        <option
                            value="{{ $service->id }}"
                            data-duration="{{ $service->duration_minutes }}"
                            data-price="{{ $service->price }}"
                            @selected(old('service_id') == $service->id)
                        >
                            {{ $service->name }} ({{ $service->duration_minutes }} دقیقه)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="staff_id">متخصص</label>
                <select id="staff_id" name="staff_id">
                    <option value="">هر متخصصی که آزاد باشد</option>
                    @foreach ($staff as $member)
                        <option value="{{ $member->id }}" @selected(old('staff_id') == $member->id)>
                            {{ $member->name }}
                        </option>
                    @endforeach
                </select>
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
            </div>
        </div>

        <div class="slots-block">
            <div class="slots-head">
                <h2>زمان‌های آزاد</h2>
                <p class="muted small" id="service-hint">اول خدمت و تاریخ را انتخاب کنید.</p>
            </div>
            <div id="slots" class="slots" aria-live="polite"></div>
        </div>

        <input type="hidden" name="starts_at" id="starts_at" value="{{ old('starts_at') }}">

        <div id="customer-block" class="customer-block" @if (! old('starts_at')) hidden @endif>
            <h2>اطلاعات شما</h2>

            <div class="grid-2">
                <div class="field">
                    <label for="name">نام و نام خانوادگی</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                </div>

                <div class="field">
                    <label for="phone">شمارهٔ همراه</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" placeholder="09121234567" required>
                </div>

                <div class="field">
                    <label for="email">ایمیل (اختیاری)</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" dir="ltr">
                </div>

                <div class="field">
                    <label for="notes">توضیح (اختیاری)</label>
                    <input type="text" id="notes" name="notes" value="{{ old('notes') }}" maxlength="500">
                </div>
            </div>

            <p class="selected-slot" id="selected-slot"></p>

            <button type="submit" class="button">ثبت نوبت</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('booking-form');
            const service = document.getElementById('service_id');
            const staff = document.getElementById('staff_id');
            const date = document.getElementById('date');
            const slotsEl = document.getElementById('slots');
            const hint = document.getElementById('service-hint');
            const startsAt = document.getElementById('starts_at');
            const customerBlock = document.getElementById('customer-block');
            const selectedSlot = document.getElementById('selected-slot');
            const slotsUrl = @json(route('public.slots', ['tenant' => $tenant->slug]));
            const faDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

            function toFa(value) {
                return String(value).replace(/\d/g, (d) => faDigits[Number(d)]);
            }

            function resetSelection() {
                startsAt.value = '';
                customerBlock.hidden = true;
                selectedSlot.textContent = '';
            }

            function renderSlots(payload) {
                slotsEl.innerHTML = '';

                if (!payload.slots || payload.slots.length === 0) {
                    hint.textContent = 'برای این روز زمان آزادی نیست. روز دیگری را امتحان کنید.';
                    return;
                }

                hint.textContent = toFa(payload.slots.length) + ' زمان آزاد در ' + toFa(payload.date);

                payload.slots.forEach(function (slot) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'slot';
                    button.textContent = toFa(slot.time) + ' تا ' + toFa(slot.end_time);

                    button.addEventListener('click', function () {
                        document.querySelectorAll('.slot.is-selected').forEach((el) => el.classList.remove('is-selected'));
                        button.classList.add('is-selected');

                        startsAt.value = payload.date + ' ' + slot.time;
                        selectedSlot.textContent = 'زمان انتخابی: ' + toFa(payload.date) + ' ساعت ' + toFa(slot.time);
                        customerBlock.hidden = false;
                        document.getElementById('name').focus();
                    });

                    slotsEl.appendChild(button);
                });
            }

            async function loadSlots() {
                resetSelection();

                if (!service.value || !date.value) {
                    slotsEl.innerHTML = '';
                    hint.textContent = 'اول خدمت و تاریخ را انتخاب کنید.';
                    return;
                }

                hint.textContent = 'در حال دریافت زمان‌ها…';
                slotsEl.innerHTML = '';

                const params = new URLSearchParams({ service_id: service.value, date: date.value });
                if (staff.value) {
                    params.set('staff_id', staff.value);
                }

                try {
                    const response = await fetch(slotsUrl + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    renderSlots(await response.json());
                } catch (error) {
                    hint.textContent = 'دریافت زمان‌ها ناموفق بود. صفحه را دوباره بارگذاری کنید.';
                }
            }

            [service, staff, date].forEach(function (element) {
                element.addEventListener('change', loadSlots);
            });

            if (service.value && date.value) {
                loadSlots();
            }
        })();
    </script>
@endpush
