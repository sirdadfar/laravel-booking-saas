<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffSchedule>
 */
class StaffScheduleFactory extends Factory
{
    protected $model = StaffSchedule::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'staff_id' => Staff::factory(),
            'weekday' => 0,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ];
    }

    public function forStaff(Staff $staff): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staff->getKey(),
        ]);
    }

    public function window(int $weekday, string $start, string $end): static
    {
        return $this->state(fn (): array => [
            'weekday' => $weekday,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}
