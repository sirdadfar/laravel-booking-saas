<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class CancelThrottleTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    public function test_cancel_attempts_are_rate_limited(): void
    {
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();

        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);
        $booking = $this->makeBooking($service, $staff, '2026-05-05 10:00');

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            // The first request cancels; the rest are refused by the state
            // machine. All of them still consume the same throttle bucket.
            $this->post('/rose/bookings/'.$booking->code.'/cancel')->assertRedirect();
        }

        $this->post('/rose/bookings/'.$booking->code.'/cancel')->assertStatus(429);
    }
}
