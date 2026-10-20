<?php

namespace Tests\Concerns;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Shared builders for tenant-scoped tests.
 *
 * Every helper runs inside the tenant context, because tenant-owned models
 * deliberately refuse to be created without one.
 */
trait BookingFixtures
{
    protected Tenant $tenant;

    protected TenantContext $context;

    protected function bootTenant(array $settings = [], string $timezone = 'Asia/Tehran', string $slug = 'rose'): Tenant
    {
        $this->tenant = Tenant::factory()->create([
            'slug' => $slug,
            'timezone' => $timezone,
            'settings' => array_merge([
                'slot_granularity_minutes' => 30,
                'booking_horizon_days' => 30,
                'cancellation_window_hours' => 6,
            ], $settings),
        ]);

        $this->context = app(TenantContext::class);
        $this->context->set($this->tenant, 'test');

        return $this->tenant;
    }

    protected function makeService(int $duration = 30, int $buffer = 0, array $attributes = []): Service
    {
        return Service::factory()->forTenant($this->tenant)->create(array_merge([
            'duration_minutes' => $duration,
            'buffer_minutes' => $buffer,
        ], $attributes));
    }

    /**
     * @param  array<int, Service>  $services
     * @param  array<int, array{0: string, 1: string}>  $windows
     * @param  array<int, int>  $weekdays  Iranian weekdays, 0 = Saturday
     */
    protected function makeStaff(
        array $services = [],
        array $windows = [['09:00', '17:00']],
        array $weekdays = [0, 1, 2, 3, 4, 5, 6],
        array $attributes = [],
    ): Staff {
        $staff = Staff::factory()->forTenant($this->tenant)->create($attributes);

        $staff->services()->sync(array_map(static fn (Service $service): int => (int) $service->getKey(), $services));

        foreach ($weekdays as $weekday) {
            foreach ($windows as [$start, $end]) {
                StaffSchedule::factory()
                    ->forStaff($staff)
                    ->window($weekday, $start, $end)
                    ->create();
            }
        }

        return $staff;
    }

    protected function makeBooking(
        Service $service,
        Staff $staff,
        string $localStart,
        ?int $minutes = null,
        BookingStatus $status = BookingStatus::Confirmed,
        ?string $phone = null,
    ): Booking {
        $customer = Customer::factory()
            ->forTenant($this->tenant)
            ->create(['phone' => $phone ?? '0912'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT)]);

        $startsAt = CarbonImmutable::parse($localStart, $this->tenant->timezone());

        return Booking::query()->create([
            'service_id' => $service->getKey(),
            'staff_id' => $staff->getKey(),
            'customer_id' => $customer->getKey(),
            'starts_at' => $startsAt->utc(),
            'ends_at' => $startsAt->addMinutes($minutes ?? $service->duration_minutes)->utc(),
            'status' => $status,
            'price' => $service->price,
        ]);
    }

    protected function timeAt(string $localDateTime): CarbonImmutable
    {
        return CarbonImmutable::parse($localDateTime, $this->tenant->timezone());
    }
}
