@php
    $uid = $service?->id ?? 'new';
    $selectedStaff = old('staff_ids', $service?->staff->pluck('id')->all() ?? []);
@endphp

<div class="grid-3">
    <div class="field">
        <label for="name-{{ $uid }}">نام خدمت</label>
        <input type="text" id="name-{{ $uid }}" name="name"
               value="{{ old('name', $service?->name) }}" required maxlength="120">
    </div>

    <div class="field">
        <label for="duration-{{ $uid }}">مدت (دقیقه)</label>
        <input type="number" id="duration-{{ $uid }}" name="duration_minutes" dir="ltr"
               min="5" max="480" step="5"
               value="{{ old('duration_minutes', $service?->duration_minutes ?? 30) }}" required>
    </div>

    <div class="field">
        <label for="buffer-{{ $uid }}">بافر پاک‌سازی (دقیقه)</label>
        <input type="number" id="buffer-{{ $uid }}" name="buffer_minutes" dir="ltr"
               min="0" max="120" step="5"
               value="{{ old('buffer_minutes', $service?->buffer_minutes ?? 0) }}">
    </div>

    <div class="field">
        <label for="price-{{ $uid }}">قیمت (تومان)</label>
        <input type="number" id="price-{{ $uid }}" name="price" dir="ltr"
               min="0" step="1000"
               value="{{ old('price', $service?->price ?? 0) }}" required>
    </div>

    <div class="field">
        <label for="color-{{ $uid }}">رنگ</label>
        <input type="color" id="color-{{ $uid }}" name="color"
               value="{{ old('color', $service?->color ?? '#0f766e') }}">
    </div>

    <div class="field">
        <label class="checkbox">
            <input type="checkbox" name="is_active" value="1"
                   @checked(old('is_active', $service?->is_active ?? true))>
            فعال (در صفحهٔ رزرو دیده شود)
        </label>
    </div>
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
