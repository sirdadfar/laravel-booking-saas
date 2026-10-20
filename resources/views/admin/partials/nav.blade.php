<nav class="sub-nav" aria-label="بخش‌های پنل مدیریت">
    <a
        href="{{ route('admin.dashboard', ['tenant' => $tenant->slug]) }}"
        class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
        @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
    >
        <x-icon name="chart" class="icon icon--sm" />
        داشبورد
    </a>
    <a
        href="{{ route('admin.bookings', ['tenant' => $tenant->slug]) }}"
        class="{{ request()->routeIs('admin.bookings') ? 'is-active' : '' }}"
        @if (request()->routeIs('admin.bookings')) aria-current="page" @endif
    >
        <x-icon name="calendar" class="icon icon--sm" />
        نوبت‌ها
    </a>
    <a
        href="{{ route('admin.services', ['tenant' => $tenant->slug]) }}"
        class="{{ request()->routeIs('admin.services') ? 'is-active' : '' }}"
        @if (request()->routeIs('admin.services')) aria-current="page" @endif
    >
        <x-icon name="scissors" class="icon icon--sm" />
        خدمات
    </a>
    <a
        href="{{ route('admin.staff', ['tenant' => $tenant->slug]) }}"
        class="{{ request()->routeIs('admin.staff') ? 'is-active' : '' }}"
        @if (request()->routeIs('admin.staff')) aria-current="page" @endif
    >
        <x-icon name="users" class="icon icon--sm" />
        تیم
    </a>
</nav>
