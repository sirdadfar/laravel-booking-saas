<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class PublicBookingHttpTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private Service $service;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();
        $this->service = $this->makeService(30, 0, ['price' => 300000, 'name' => 'کوتاهی مو']);
        $this->staff = $this->makeStaff([$this->service], attributes: ['name' => 'مریم رستمی']);
    }

    public function test_the_public_booking_page_renders(): void
    {
        $response = $this->get('/rose');

        $response->assertOk();
        $response->assertSee($this->tenant->name);
        $response->assertSee('کوتاهی مو');
        $response->assertSee('مریم رستمی');
    }

    public function test_the_slots_endpoint_returns_available_times(): void
    {
        $response = $this->getJson(sprintf('/rose/slots?service_id=%d&date=2026-05-05', $this->service->id));

        $response->assertOk();
        $response->assertJsonPath('timezone', 'Asia/Tehran');
        $response->assertJsonCount(16, 'slots');
        $response->assertJsonPath('slots.0.time', '09:00');
        $response->assertJsonPath('slots.0.staff_ids', [$this->staff->id]);
    }

    public function test_the_slots_endpoint_validates_its_input(): void
    {
        $this->getJson('/rose/slots?date=2026-05-05')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_id']);

        $this->getJson(sprintf('/rose/slots?service_id=%d&date=not-a-date', $this->service->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_a_customer_can_book_and_is_redirected_to_the_confirmation(): void
    {
        $response = $this->post('/rose/bookings', [
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'starts_at' => '2026-05-05 10:00',
            'name' => 'سحر موسوی',
            'phone' => '09121234567',
        ]);

        $booking = Booking::query()->firstOrFail();

        $response->assertRedirect('/rose/bookings/'.$booking->code);

        $this->assertSame(300000, $booking->price);
        $this->assertSame('2026-05-05 10:00', $booking->startsAtIn('Asia/Tehran')->format('Y-m-d H:i'));
        $this->assertSame($this->tenant->id, $booking->tenant_id);
    }

    public function test_a_phone_number_that_is_not_an_iranian_mobile_is_rejected(): void
    {
        $response = $this->from('/rose')->post('/rose/bookings', [
            'service_id' => $this->service->id,
            'starts_at' => '2026-05-05 10:00',
            'name' => 'سحر موسوی',
            'phone' => '12345',
        ]);

        $response->assertRedirect('/rose');
        $response->assertSessionHasErrors('phone');
        $this->assertSame(0, Booking::query()->count());
    }

    public function test_a_taken_slot_returns_a_conflict_instead_of_double_booking(): void
    {
        $this->makeBooking($this->service, $this->staff, '2026-05-05 10:00');

        $this->postJson('/rose/bookings', [
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'starts_at' => '2026-05-05 10:00',
            'name' => 'دوم',
            'phone' => '09120000001',
        ])->assertStatus(409);

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_the_confirmation_page_shows_the_booking(): void
    {
        $booking = $this->makeBooking($this->service, $this->staff, '2026-05-05 10:00');

        $response = $this->get('/rose/bookings/'.$booking->code);

        $response->assertOk();
        $response->assertSee($booking->code);
        $response->assertSee('کوتاهی مو');
        $response->assertSee('لغو نوبت');
    }

    public function test_a_customer_can_cancel_inside_the_window(): void
    {
        $booking = $this->makeBooking($this->service, $this->staff, '2026-05-05 10:00');

        $this->post('/rose/bookings/'.$booking->code.'/cancel')
            ->assertRedirect('/rose/bookings/'.$booking->code);

        $this->assertSame('cancelled', $booking->fresh()->status->value);
    }

    public function test_a_foreign_service_id_is_not_found(): void
    {
        $other = Tenant::factory()->create();
        $foreign = app(TenantContext::class)->runFor(
            $other,
            fn () => Service::factory()->forTenant($other)->create(),
        );

        $this->getJson(sprintf('/rose/slots?service_id=%d&date=2026-05-05', $foreign->id))
            ->assertNotFound();
    }

    public function test_customer_input_is_escaped_on_the_confirmation_page(): void
    {
        $this->post('/rose/bookings', [
            'service_id' => $this->service->id,
            'starts_at' => '2026-05-05 10:00',
            'name' => '<script>alert(1)</script>',
            'phone' => '09121234567',
        ])->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        $this->get('/rose/bookings/'.$booking->code)
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_an_unknown_tenant_returns_404(): void
    {
        $this->get('/does-not-exist')->assertNotFound();
    }

    public function test_an_inactive_tenant_returns_404(): void
    {
        Tenant::factory()->inactive()->create(['slug' => 'closed-shop']);

        $this->get('/closed-shop')->assertNotFound();
    }
}
