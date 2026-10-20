<nav class="sub-nav">
    <a href="{{ route('admin.dashboard', ['tenant' => $tenant->slug]) }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">داشبورد</a>
    <a href="{{ route('admin.bookings', ['tenant' => $tenant->slug]) }}" class="{{ request()->routeIs('admin.bookings') ? 'is-active' : '' }}">نوبت‌ها</a>
    <a href="{{ route('admin.services', ['tenant' => $tenant->slug]) }}" class="{{ request()->routeIs('admin.services') ? 'is-active' : '' }}">خدمات</a>
    <a href="{{ route('admin.staff', ['tenant' => $tenant->slug]) }}" class="{{ request()->routeIs('admin.staff') ? 'is-active' : '' }}">تیم</a>
</nav>
