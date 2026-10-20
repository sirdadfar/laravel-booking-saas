<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TimeOff;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private AvailabilityService $availability;

    protected function setUp(): void
    {
        parent::setUp();

        // A Monday morning; every test books on Tuesday 2026-05-05.
        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();
        $this->availability = app(AvailabilityService::class);
    }

    private function tuesday(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-05-05', 'Asia/Tehran');
    }

    /**
     * @return array<int, string>
     */
    private function labels(array $slots): array
    {
        return array_map(static fn ($slot): string => $slot->timeLabel(), $slots);
    }

    public function test_it_lists_every_start_time_inside_the_working_window(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $slots = $this->availability->slots($this->tenant, $service, null, $this->tuesday());

        $this->assertCount(16, $slots);
        $this->assertSame('09:00', $slots[0]->timeLabel());
        $this->assertSame('16:30', end($slots)->timeLabel());
        $this->assertSame([$staff->id], $slots[0]->staffIds);
    }

    public function test_the_cleanup_buffer_must_fit_before_the_window_closes(): void
    {
        $service = $this->makeService(30, 15);
        $this->makeStaff([$service]);

        $slots = $this->availability->slots($this->tenant, $service, null, $this->tuesday());

        $this->assertCount(15, $slots);
        $this->assertSame('16:00', end($slots)->timeLabel());
    }

    public function test_an_existing_booking_removes_its_start_time(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);
        $this->makeBooking($service, $staff, '2026-05-05 10:00');

        $labels = $this->labels($this->availability->slots($this->tenant, $service, null, $this->tuesday()));

        $this->assertNotContains('10:00', $labels);
        $this->assertContains('10:30', $labels);
        $this->assertCount(15, $labels);
    }

    public function test_a_cancelled_booking_frees_the_slot_again(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);
        $this->makeBooking($service, $staff, '2026-05-05 10:00', status: BookingStatus::Cancelled);

        $labels = $this->labels($this->availability->slots($this->tenant, $service, null, $this->tuesday()));

        $this->assertContains('10:00', $labels);
        $this->assertCount(16, $labels);
    }

    public function test_time_off_blocks_every_overlapping_start(): void
    {
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        TimeOff::query()->create([
            'staff_id' => $staff->id,
            'starts_at' => $this->timeAt('2026-05-05 12:00')->utc(),
            'ends_at' => $this->timeAt('2026-05-05 13:00')->utc(),
            'reason' => 'مرخصی',
        ]);

        $labels = $this->labels($this->availability->slots($this->tenant, $service, null, $this->tuesday()));

        $this->assertNotContains('12:00', $labels);
        $this->assertNotContains('12:30', $labels);
        $this->assertContains('11:30', $labels);
        $this->assertContains('13:00', $labels);
    }

    public function test_a_weekday_without_a_schedule_has_no_slots(): void
    {
        $service = $this->makeService(30);
        $this->makeStaff([$service], weekdays: [0, 1, 2, 3, 4, 5]); // Friday (6) is closed

        $friday = CarbonImmutable::parse('2026-05-08', 'Asia/Tehran');

        $this->assertSame([], $this->availability->slots($this->tenant, $service, null, $friday));
    }

    public function test_past_start_times_are_not_offered_for_today(): void
    {
        $service = $this->makeService(30);
        $this->makeStaff([$service]);

        $this->travelTo(CarbonImmutable::parse('2026-05-04 10:10:00', 'Asia/Tehran'));

        $slots = $this->availability->slots(
            $this->tenant,
            $service,
            null,
            CarbonImmutable::parse('2026-05-04', 'Asia/Tehran'),
        );

        $this->assertSame('10:30', $slots[0]->timeLabel());
        $this->assertNotContains('10:00', $this->labels($slots));
    }

    public function test_dates_beyond_the_booking_horizon_have_no_slots(): void
    {
        $service = $this->makeService(30);
        $this->makeStaff([$service]);

        $tooFar = $this->tuesday()->addDays(33);

        $this->assertSame([], $this->availability->slots($this->tenant, $service, null, $tooFar));
    }

    public function test_a_staff_member_only_offers_services_they_perform(): void
    {
        $service = $this->makeService(30);
        $qualified = $this->makeStaff([$service], attributes: ['name' => 'واجد شرایط']);
        $other = $this->makeStaff([], attributes: ['name' => 'بدون این خدمت']);

        $slots = $this->availability->slots($this->tenant, $service, null, $this->tuesday());

        $this->assertSame([$qualified->id], $slots[0]->staffIds);
        $this->assertSame([], $this->availability->slots($this->tenant, $service, $other, $this->tuesday()));
    }

    public function test_a_slot_is_offered_when_any_qualified_staff_member_is_free(): void
    {
        $service = $this->makeService(30);
        $first = $this->makeStaff([$service], attributes: ['name' => 'اول']);
        $second = $this->makeStaff([$service], attributes: ['name' => 'دوم']);

        $this->makeBooking($service, $first, '2026-05-05 09:00');

        $slots = $this->availability->slots($this->tenant, $service, null, $this->tuesday());

        $this->assertContains('09:00', $this->labels($slots));
        $this->assertSame([$second->id], $slots[0]->staffIds);
    }

    public function test_slot_granularity_comes_from_the_tenant_settings(): void
    {
        $this->tenant->update([
            'settings' => array_merge($this->tenant->settings, ['slot_granularity_minutes' => 20]),
        ]);

        $service = $this->makeService(30);
        $this->makeStaff([$service]);

        $labels = $this->labels($this->availability->slots($this->tenant, $service, null, $this->tuesday()));

        $this->assertContains('09:00', $labels);
        $this->assertContains('09:20', $labels);
        $this->assertContains('09:40', $labels);
        $this->assertNotContains('09:30', $labels);
    }

    public function test_slots_are_expressed_in_the_tenant_timezone(): void
    {
        $this->tenant->update(['timezone' => 'Asia/Kabul']);

        $service = $this->makeService(30);
        $this->makeStaff([$service]);

        $slot = $this->availability->slots($this->tenant, $service, null, $this->tuesday())[0];

        $this->assertSame('Asia/Kabul', $slot->startsAt->timezoneName);
        $this->assertSame('09:00', $slot->timeLabel());
        $this->assertSame('2026-05-05T04:30:00+00:00', $slot->startsAt->utc()->toIso8601String());
    }

    public function test_an_inactive_service_has_no_slots(): void
    {
        $service = $this->makeService(30, 0, ['is_active' => false]);
        $this->makeStaff([$service]);

        $this->assertSame([], $this->availability->slots($this->tenant, $service, null, $this->tuesday()));
    }

    public function test_a_service_from_another_tenant_is_rejected(): void
    {
        $other = Tenant::factory()->create();
        $foreignService = Service::factory()->forTenant($other)->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->availability->slots($this->tenant, $foreignService, null, $this->tuesday());
    }
}
