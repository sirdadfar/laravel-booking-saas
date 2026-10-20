<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    public function test_the_public_booking_endpoint_is_rate_limited(): void
    {
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();
        $service = $this->makeService(30);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            // Deliberately invalid payloads: validation failures still count
            // towards the limit, otherwise the endpoint is trivial to spam.
            $this->post('/rose/bookings', [
                'service_id' => $service->id,
                'starts_at' => '2026-05-05 10:00',
            ])->assertRedirect();
        }

        $this->post('/rose/bookings', [
            'service_id' => $service->id,
            'starts_at' => '2026-05-05 10:00',
        ])->assertStatus(429);
    }
}
