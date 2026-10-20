<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\BookingRuleException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Notifications\BookingConfirmed;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private BookingService $bookings;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();
        $this->bookings = app(BookingService::class);
    }

    private function tomorrowAt(string $time): CarbonImmutable
    {
        return $this->timeAt('2026-05-05 '.$time);
    }

    public function test_it_creates_a_confirmed_booking_and_a_customer(): void
    {
        $service = $this->makeService(45, 10, ['price' => 500000]);
        $staff = $this->makeStaff([$service]);

        $booking = $this->bookings->book(
            $this->tenant,
            $service,
            $staff,
            $this->tomorrowAt('10:00'),
            ['name' => 'سحر موسوی', 'phone' => '09121234567'],
        );

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(500000, $booking->price);
        $this->assertSame('2026-05-05 10:00', $booking->startsAtIn('Asia/Tehran')->format('Y-m-d H:i'));
        $this->assertSame('2026-05-05 10:45', $booking->endsAtIn('Asia/Tehran')->format('Y-m-d H:i'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'tenant_id' => $this->tenant->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'phone' => '09121234567',
            'name' => 'سحر موسوی',
        ]);
    }

    public function test_the_cleanup_buffer_is_not_part_of_the_stored_end_time(): void
    {
        $service = $this->makeService(30, 20);
        $staff = $this->makeStaff([$service]);

        $booking = $this->bookings->book(
            $this->tenant,
            $service,
            $staff,
            $this->tomorrowAt('11:00'),
            ['name' => 'مشتری', 'phone' => '09120000001'],
        );

        $this->assertSame(30, $booking->durationMinutes());
    }

    public function test_persian_digits_in_the_phone_are_normalised(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $this->bookings->book(
            $this->tenant,
            $service,
            $staff,
            $this->tomorrowAt('09:00'),
            ['name' => 'مشتری', 'phone' => '۰۹۱۲۳۴۵۶۷۸۹'],
        );

        $this->assertDatabaseHas('customers', ['phone' => '09123456789']);
    }

    public function test_a_returning_customer_is_reused_instead_of_duplicated(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('09:00'), [
            'name' => 'مشتری',
            'phone' => '09121112222',
        ]);

        $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('11:00'), [
            'name' => 'مشتری',
            'phone' => '09121112222',
        ]);

        $this->assertSame(1, Customer::query()->where('phone', '09121112222')->count());
        $this->assertSame(2, Booking::query()->count());
    }

    public function test_the_same_slot_cannot_be_booked_twice(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('10:00'), [
            'name' => 'اول',
            'phone' => '09120000001',
        ]);

        $this->expectException(SlotUnavailableException::class);

        $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('10:00'), [
            'name' => 'دوم',
            'phone' => '09120000002',
        ]);
    }

    public function test_a_past_time_is_rejected(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $this->expectException(BookingRuleException::class);

        $this->bookings->book($this->tenant, $service, $staff, $this->timeAt('2026-05-03 10:00'), [
            'name' => 'مشتری',
            'phone' => '09120000003',
        ]);
    }

    public function test_a_staff_member_who_does_not_perform_the_service_is_rejected(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([]);

        $this->expectException(BookingRuleException::class);

        $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('10:00'), [
            'name' => 'مشتری',
            'phone' => '09120000004',
        ]);
    }

    public function test_an_invalid_phone_number_is_rejected(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $this->expectException(BookingRuleException::class);

        $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('10:00'), [
            'name' => 'مشتری',
            'phone' => '12345',
        ]);
    }

    public function test_a_time_outside_the_booking_horizon_is_rejected(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);
        $date = CarbonImmutable::parse('2026-06-20 10:00', 'Asia/Tehran');

        $this->expectException(BookingRuleException::class);

        $this->bookings->book($this->tenant, $service, $staff, $date, [
            'name' => 'مشتری',
            'phone' => '09120000005',
        ]);
    }

    public function test_cancelling_frees_the_slot_and_records_the_reason(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $booking = $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('10:00'), [
            'name' => 'مشتری',
            'phone' => '09120000006',
        ]);

        $this->bookings->cancel($booking, 'برنامه تغییر کرد');

        $booking->refresh();

        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertSame('برنامه تغییر کرد', $booking->cancel_reason);

        $again = $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('10:00'), [
            'name' => 'مشتری دیگر',
            'phone' => '09120000007',
        ]);

        $this->assertSame(BookingStatus::Confirmed, $again->status);
    }

    public function test_the_customer_cancellation_window_is_enforced(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $booking = $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('10:00'), [
            'name' => 'مشتری',
            'phone' => '09120000008',
        ]);

        $this->travelTo($this->timeAt('2026-05-05 08:00'));

        $this->expectException(BookingRuleException::class);

        $this->bookings->cancel($booking, null, true);
    }

    public function test_a_finished_booking_cannot_be_confirmed_again(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);
        $booking = $this->makeBooking($service, $staff, '2026-05-05 09:00', status: BookingStatus::Cancelled);

        $this->expectException(BookingRuleException::class);

        $this->bookings->transition($booking, BookingStatus::Confirmed);
    }

    public function test_confirming_a_pending_booking_sends_the_notification(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);
        $booking = $this->makeBooking($service, $staff, '2026-05-05 09:00', status: BookingStatus::Pending);

        $this->bookings->transition($booking, BookingStatus::Confirmed);

        Notification::assertSentTo($booking->customer, BookingConfirmed::class);
    }

    public function test_the_price_is_snapshotted_on_the_booking(): void
    {
        $service = $this->makeService(30, 0, ['price' => 100000]);
        $staff = $this->makeStaff([$service]);

        $booking = $this->bookings->book($this->tenant, $service, $staff, $this->tomorrowAt('09:00'), [
            'name' => 'مشتری',
            'phone' => '09120000009',
        ]);

        $service->update(['price' => 900000]);

        $this->assertSame(100000, $booking->fresh()->price);
    }

    public function test_a_booking_without_a_staff_choice_is_assigned_a_free_specialist(): void
    {
        $service = $this->makeService(30);
        $first = $this->makeStaff([$service]);
        $second = $this->makeStaff([$service]);

        $this->bookings->book($this->tenant, $service, null, $this->tomorrowAt('09:00'), [
            'name' => 'اول',
            'phone' => '09120000010',
        ]);

        $secondBooking = $this->bookings->book($this->tenant, $service, null, $this->tomorrowAt('09:00'), [
            'name' => 'دوم',
            'phone' => '09120000011',
        ]);

        $this->assertSame($second->id, $secondBooking->staff_id);
        $this->assertNotSame($first->id, $secondBooking->staff_id);
    }
}
