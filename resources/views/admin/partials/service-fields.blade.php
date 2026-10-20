@php
    $uid = $service?->id ?? 'new';
    $selectedStaff = old('staff_ids', $service?->staff->pluck('id')->all() ?? []);
@endphp

<div class="field-grid">
    <div class="field">
        <label for="name-{{ $uid }}">نام خدمت</label>
        <input type="text" id="name-{{ $uid }}" name="name" class="input"
               value="{{ old('name', $service?->name) }}" required maxlength="120">
    </div>

    <div class="field">
        <label for="duration-{{ $uid }}">مدت (دقیقه)</label>
        <input type="number" id="duration-{{ $uid }}" name="duration_minutes" class="input" dir="ltr"
               min="5" max="480" step="5"
               value="{{ old('duration_minutes', $service?->duration_minutes ?? 30) }}" required>
    </div>

    <div class="field">
        <label for="buffer-{{ $uid }}">بافر پاک‌سازی (دقیقه)</label>
        <input type="number" id="buffer-{{ $uid }}" name="buffer_minutes" class="input" dir="ltr"
               min="0" max="120" step="5"
               value="{{ old('buffer_minutes', $service?->buffer_minutes ?? 0) }}">
        <span class="field__hint">فاصلهٔ خالی بعد از هر نوبت، قبل از نوبت بعدی.</span>
    </div>

    <div class="field">
        <label for="price-{{ $uid }}">قیمت (تومان)</label>
        <input type="number" id="price-{{ $uid }}" name="price" class="input" dir="ltr"
               min="0" step="1000"
               value="{{ old('price', $service?->price ?? 0) }}" required>
    </div>

    <div class="field">
        <label for="color-{{ $uid }}">رنگ</label>
        <input type="color" id="color-{{ $uid }}" name="color"
               value="{{ old('color', $service?->color ?? '#0f766e') }}">
    </div>
</div>

<div class="field">
    <span class="field__label">وضعیت نمایش</span>
    <label class="checkbox">
        <input type="checkbox" name="is_active" value="1"
               @checked(old('is_active', $service?->is_active ?? true))>
        فعال (در صفحهٔ رزرو دیده شود)
    </label>
</div>

<fieldset class="fieldset">
    <legend>کدام متخصص‌ها این خدمت را انجام می‌دهند؟</legend>
    <div class="checkbox-row">
        @forelse ($staff as $member)
            <label class="checkbox">
                <input type="checkbox" name="staff_ids[]" value="{{ $member->id }}"
                       @checked(in_array($member->id, array_map('intval', (array) $selectedStaff), true))>
                {{ $member->name }}
            </label>
        @empty
            <p class="muted small">هنوز عضوی به تیم اضافه نشده است. اول از بخش «تیم» عضو بسازید.</p>
        @endforelse
    </div>
</fieldset>
