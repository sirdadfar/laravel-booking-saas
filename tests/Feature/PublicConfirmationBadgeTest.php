<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class PublicConfirmationBadgeTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    public function test_a_pending_badge_on_the_public_page_uses_a_styled_modifier(): void
    {
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();

        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);
        $booking = $this->makeBooking($service, $staff, '2026-05-05 10:00', status: BookingStatus::Pending);

        $this->get('/rose/bookings/'.$booking->code)
            ->assertOk()
            ->assertSee('badge badge-warn', false)
            ->assertDontSee('badge-warning', false);
    }
}
