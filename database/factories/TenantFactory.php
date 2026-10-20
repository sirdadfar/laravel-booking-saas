<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'timezone' => 'Asia/Tehran',
            'phone' => '021'.fake()->numerify('########'),
            'address' => fake()->address(),
            'is_active' => true,
            'settings' => [
                'slot_granularity_minutes' => 30,
                'booking_horizon_days' => 30,
                'cancellation_window_hours' => 6,
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
