<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Tenant;
use App\Models\TimeOff;
use App\Scopes\TenantScope;
use App\Support\AvailableSlot;
use App\Support\IranianWeek;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Availability engine.
 *
 * All computations happen in the tenant's timezone. Slots are generated from
 * the start of each working window in steps of the tenant's slot granularity.
 * A candidate is offered only when the full service duration PLUS the cleanup
 * buffer fits before the window closes, and the whole occupied range is free
 * of blocking bookings and time off.
 */
class AvailabilityService
{
    /**
     * @return array<int, AvailableSlot>
     */
    public function slots(
        Tenant $tenant,
        Service $service,
        ?Staff $staff,
        CarbonImmutable $date,
    ): array {
        if ((int) $service->tenant_id !== (int) $tenant->getKey()) {
            throw new InvalidArgumentException('Service does not belong to the tenant.');
        }

        if ($staff !== null && (int) $staff->tenant_id !== (int) $tenant->getKey()) {
            throw new InvalidArgumentException('Staff member does not belong to the tenant.');
        }

        $timezone = $tenant->timezone();
        $day = $date->setTimezone($timezone)->startOfDay();
        $now = CarbonImmutable::now($timezone);
        $today = $now->startOfDay();

        if ($day->lessThan($today)) {
            return [];
        }

        if ($day->greaterThan($today->addDays($tenant->bookingHorizonDays()))) {
            return [];
        }

        $eligibleStaffIds = $this->eligibleStaffIds($tenant, $service);

        if ($staff !== null) {
            if (! $staff->is_active || ! in_array((int) $staff->getKey(), $eligibleStaffIds, true)) {
                return [];
            }

            $eligibleStaffIds = [(int) $staff->getKey()];
        }

        if ($eligibleStaffIds === []) {
            return [];
        }

        $weekday = IranianWeek::weekday($day);

        $schedules = StaffSchedule::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->whereIn('staff_id', $eligibleStaffIds)
            ->where('weekday', $weekday)
            ->orderBy('start_time')
            ->get()
            ->groupBy('staff_id');

        if ($schedules->isEmpty()) {
            return [];
        }

        $busy = $this->busyIntervals($tenant, $eligibleStaffIds, $day);
        $granularity = $tenant->slotGranularityMinutes();
        $occupiedMinutes = $service->duration_minutes + $service->buffer_minutes;
        $collected = [];

        foreach ($eligibleStaffIds as $staffId) {
            foreach ($schedules->get($staffId, collect()) as $schedule) {
                $windowStart = $this->atTime($day, $schedule->start_time);
                $windowEnd = $this->atTime($day, $schedule->end_time);

                if ($windowEnd->lessThanOrEqualTo($windowStart)) {
                    continue;
                }

                for (
                    $start = $windowStart;
                    $start->addMinutes($occupiedMinutes)->lessThanOrEqualTo($windowEnd);
                    $start = $start->addMinutes($granularity)
                ) {
                    if ($start->lessThan($now)) {
                        continue;
                    }

                    $occupiedEnd = $start->addMinutes($occupiedMinutes);

                    if ($this->overlapsAny($start, $occupiedEnd, $busy[$staffId] ?? [])) {
                        continue;
                    }

                    $key = $start->format('Y-m-d H:i');

                    $collected[$key] ??= ['start' => $start, 'staff' => []];
                    $collected[$key]['staff'][] = $staffId;
                }
            }
        }

        ksort($collected);

        return array_values(array_map(
            fn (array $item): AvailableSlot => new AvailableSlot(
                $item['start'],
                $item['start']->addMinutes($service->duration_minutes),
                array_values(array_unique($item['staff'])),
            ),
            $collected,
        ));
    }

    public function isSlotAvailable(
        Tenant $tenant,
        Service $service,
        ?Staff $staff,
        CarbonImmutable $startsAt,
    ): bool {
        $timezone = $tenant->timezone();
        $wanted = $startsAt->setTimezone($timezone);
        $wantedKey = $wanted->format('Y-m-d H:i');

        foreach ($this->slots($tenant, $service, $staff, $wanted) as $slot) {
            if ($slot->startsAt->format('Y-m-d H:i') === $wantedKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Active staff who can perform the service inside this tenant.
     *
     * @return array<int, int>
     */
    private function eligibleStaffIds(Tenant $tenant, Service $service): array
    {
        if (! $service->is_active) {
            return [];
        }

        $staffIds = DB::table('staff_service')
            ->where('service_id', $service->getKey())
            ->pluck('staff_id')
            ->all();

        if ($staffIds === []) {
            return [];
        }

        return Staff::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->where('is_active', true)
            ->whereIn('id', $staffIds)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $staffIds
     * @return array<int, array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>>
     */
    private function busyIntervals(Tenant $tenant, array $staffIds, CarbonImmutable $day): array
    {
        $dayStartUtc = $day->startOfDay()->utc();
        // Look slightly before the day so a booking with a cleanup buffer that
        // crosses midnight still blocks an early slot.
        $lookBehindUtc = $dayStartUtc->subDay();
        $dayEndUtc = $day->addDay()->startOfDay()->utc();

        $buffers = Service::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->pluck('buffer_minutes', 'id');

        $busy = [];

        $bookings = Booking::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->whereIn('staff_id', $staffIds)
            ->whereIn('status', BookingStatus::blockingValues())
            ->where('starts_at', '<', $dayEndUtc)
            ->where('ends_at', '>', $lookBehindUtc)
            ->get(['id', 'staff_id', 'service_id', 'starts_at', 'ends_at']);

        foreach ($bookings as $booking) {
            $buffer = (int) ($buffers[$booking->service_id] ?? 0);

            $busy[(int) $booking->staff_id][] = [
                CarbonImmutable::instance($booking->starts_at),
                CarbonImmutable::instance($booking->ends_at)->addMinutes($buffer),
            ];
        }

        $timeOffs = TimeOff::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->whereIn('staff_id', $staffIds)
            ->where('starts_at', '<', $dayEndUtc)
            ->where('ends_at', '>', $lookBehindUtc)
            ->get(['staff_id', 'starts_at', 'ends_at']);

        foreach ($timeOffs as $timeOff) {
            $busy[(int) $timeOff->staff_id][] = [
                CarbonImmutable::instance($timeOff->starts_at),
                CarbonImmutable::instance($timeOff->ends_at),
            ];
        }

        return $busy;
    }

    /**
     * @param  array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>  $intervals
     */
    private function overlapsAny(CarbonImmutable $start, CarbonImmutable $end, array $intervals): bool
    {
        foreach ($intervals as [$blockedStart, $blockedEnd]) {
            if ($start->lessThan($blockedEnd) && $end->greaterThan($blockedStart)) {
                return true;
            }
        }

        return false;
    }

    private function atTime(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $day->setTime($hours, $minutes);
    }
}
