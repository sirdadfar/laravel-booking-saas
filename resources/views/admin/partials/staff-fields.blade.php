@php
    $uid = $member?->id ?? 'new';
    $selectedServices = old('service_ids', $member?->services->pluck('id')->all() ?? []);
@endphp

<div class="grid-2">
    <div class="field">
        <label for="staff-name-{{ $uid }}">نام</label>
        <input type="text" id="staff-name-{{ $uid }}" name="name"
               value="{{ old('name', $member?->name) }}" required maxlength="120">
    </div>

    <div class="field">
        <label for="staff-phone-{{ $uid }}">شمارهٔ همراه (اختیاری)</label>
        <input type="tel" id="staff-phone-{{ $uid }}" name="phone" dir="ltr"
               value="{{ old('phone', $member?->phone) }}" placeholder="09121234567">
    </div>
</div>

<label class="checkbox">
    <input type="checkbox" name="is_active" value="1"
           @checked(old('is_active', $member?->is_active ?? true))>
    فعال (در صفحهٔ رزرو دیده شود)
</label>

<fieldset class="fieldset">
    <legend>خدمات قابل ارائه</legend>
    <div class="checkbox-row">
        @forelse ($services as $service)
            <label class="checkbox">
                <input type="checkbox" name="service_ids[]" value="{{ $service->id }}"
                       @checked(in_array($service->id, array_map('intval', (array) $selectedServices), true))>
                {{ $service->name }}
            </label>
        @empty
            <p class="muted small">هنوز خدمتی ثبت نشده است. اول از بخش «خدمات» خدمت بسازید.</p>
        @endforelse
    </div>
</fieldset>

<fieldset class="fieldset">
    <legend>ساعات کاری هفتگی</legend>

    <div class="shifts-grid">
        @foreach (\App\Support\IranianWeek::LABELS as $weekday => $label)
            @php
                $value = $member?->schedules
                    ->where('weekday', $weekday)
                    ->map(fn ($schedule) => $schedule->start_time.'-'.$schedule->end_time)
                    ->join(', ');
            @endphp

            <div class="field">
                <label for="shift-{{ $uid }}-{{ $weekday }}">{{ $label }}</label>
                <input type="text"
                       id="shift-{{ $uid }}-{{ $weekday }}"
                       name="shifts[{{ $weekday }}]"
                       dir="ltr"
                       placeholder="09:00-13:00, 16:00-20:00"
                       value="{{ old('shifts.'.$weekday, $value) }}">
            </div>
        @endforeach
    </div>

    <p class="muted small">خانه‌گذاشتن یک روز یعنی تعطیل بودن آن روز.</p>
</fieldset>
