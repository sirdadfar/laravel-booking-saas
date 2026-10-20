<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement(['کوتاهی مو', 'رنگ مو', 'مانیکور', 'اصلاح ریش', 'ماساژ صورت']),
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'price' => 250000,
            'is_active' => true,
            'color' => '#0f766e',
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->getKey()]);
    }

    public function duration(int $minutes, int $buffer = 0): static
    {
        return $this->state(fn (): array => [
            'duration_minutes' => $minutes,
            'buffer_minutes' => $buffer,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
