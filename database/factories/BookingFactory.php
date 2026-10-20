<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Defaults create unrelated models; use forTenant() (or pass explicit ids)
 * to get a booking whose service, staff and customer belong to one tenant.
 *
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $startsAt = CarbonImmutable::now('Asia/Tehran')->addDay()->setTime(10, 0);

        return [
            'tenant_id' => Tenant::factory(),
            'service_id' => Service::factory(),
            'staff_id' => Staff::factory(),
            'customer_id' => Customer::factory(),
            'code' => 'NB-'.Str::upper(Str::random(6)),
            'starts_at' => $startsAt->utc(),
            'ends_at' => $startsAt->addMinutes(30)->utc(),
            'status' => BookingStatus::Confirmed,
            'notes' => null,
            'price' => 250000,
            'cancelled_at' => null,
            'cancel_reason' => null,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(function () use ($tenant): array {
            $service = Service::factory()->forTenant($tenant)->create();
            $staff = Staff::factory()->forTenant($tenant)->create();
            $customer = Customer::factory()->forTenant($tenant)->create();

            return [
                'tenant_id' => $tenant->getKey(),
                'service_id' => $service->getKey(),
                'staff_id' => $staff->getKey(),
                'customer_id' => $customer->getKey(),
                'price' => $service->price,
            ];
        });
    }

    public function at(string $localDateTime, int $minutes = 30): static
    {
        return $this->state(function () use ($localDateTime, $minutes): array {
            $startsAt = CarbonImmutable::parse($localDateTime, 'Asia/Tehran');

            return [
                'starts_at' => $startsAt->utc(),
                'ends_at' => $startsAt->addMinutes($minutes)->utc(),
            ];
        });
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
