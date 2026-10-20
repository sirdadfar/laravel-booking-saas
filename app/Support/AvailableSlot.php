<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * One bookable start time. `startsAt` and `endsAt` are in the tenant timezone.
 * `endsAt` is the end of the service itself; the cleanup buffer is only used
 * internally by the availability engine and is not part of the public slot.
 */
final readonly class AvailableSlot
{
    /**
     * @param  array<int, int>  $staffIds  staff members who can take this slot
     */
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public array $staffIds,
    ) {}

    public function timeLabel(): string
    {
        return $this->startsAt->format('H:i');
    }

    public function endLabel(): string
    {
        return $this->endsAt->format('H:i');
    }

    public function localDate(): string
    {
        return $this->startsAt->format('Y-m-d');
    }

    public function isFreeFor(int $staffId): bool
    {
        return in_array($staffId, $this->staffIds, true);
    }

    /**
     * @return array{starts_at: string, ends_at: string, time: string, end_time: string, staff_ids: array<int, int>}
     */
    public function toArray(): array
    {
        return [
            'starts_at' => $this->startsAt->toIso8601String(),
            'ends_at' => $this->endsAt->toIso8601String(),
            'time' => $this->timeLabel(),
            'end_time' => $this->endLabel(),
            'staff_ids' => $this->staffIds,
        ];
    }
}
