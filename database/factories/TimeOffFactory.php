<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\Tenant;
use App\Models\TimeOff;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeOff>
 */
class TimeOffFactory extends Factory
{
    protected $model = TimeOff::class;

    public function definition(): array
    {
        $start = CarbonImmutable::now('Asia/Tehran')->addDay()->setTime(12, 0);

        return [
            'tenant_id' => Tenant::factory(),
            'staff_id' => Staff::factory(),
            'starts_at' => $start->utc(),
            'ends_at' => $start->addHour()->utc(),
            'reason' => 'مرخصی',
        ];
    }

    public function forStaff(Staff $staff): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staff->getKey(),
        ]);
    }
}
