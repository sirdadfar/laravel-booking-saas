<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\BookingRuleException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Notifications\BookingConfirmed;
use App\Scopes\TenantScope;
use App\Support\AvailableSlot;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Create a booking.
     *
     * `ends_at` is starts_at + service duration. The cleanup buffer is only
     * used by the availability engine; it is deliberately NOT included in the
     * stored end time.
     *
     * @param  array{name: string, phone: string, email?: ?string}  $customerData
     *
     * @throws BookingRuleException|SlotUnavailableException
     */
    public function book(
        Tenant $tenant,
        Service $service,
        ?Staff $staff,
        CarbonImmutable $startsAt,
        array $customerData,
        ?string $notes = null,
    ): Booking {
        $timezone = $tenant->timezone();
        $localStart = $startsAt->setTimezone($timezone)->seconds(0);
        $now = CarbonImmutable::now($timezone);

        $this->assertRequestIsValid($tenant, $service, $staff, $localStart, $now, $customerData);

        $phone = Phone::normalize($customerData['phone']);

        [$booking, $customer] = DB::transaction(function () use (
            $tenant, $service, $staff, $localStart, $customerData, $notes, $phone
        ): array {
            // Serialize writes for this tenant. Locking the tenant row (not
            // just the staff rows) also protects the two other race-prone
            // writes in this transaction: the per-tenant booking-code
            // uniqueness check and firstOrCreate() on customers
            // (tenant_id, phone). SQLite ignores row locks and serializes
            // writers at the file level instead.
            Tenant::query()->whereKey($tenant->getKey())->lockForUpdate()->first();

            $initial = $this->findSlot($tenant, $service, $staff, $localStart);

            if ($initial === null) {
                throw new SlotUnavailableException;
            }

            $idsToLock = $staff !== null ? [(int) $staff->getKey()] : $initial->staffIds;

            // Serializes concurrent bookings for the same staff member on
            // databases that support row locks (MySQL/PostgreSQL). SQLite
            // serializes writers at the database level instead.
            Staff::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->getKey())
                ->whereIn('id', $idsToLock)
                ->lockForUpdate()
                ->get();

            // Re-check AFTER acquiring the lock. A competing request may have
            // committed while we were waiting.
            $slot = $this->findSlot($tenant, $service, $staff, $localStart);

            if ($slot === null) {
                throw new SlotUnavailableException;
            }

            $resolvedStaffId = $staff?->getKey();

            if ($resolvedStaffId === null) {
                $resolvedStaffId = $slot->staffIds[0] ?? null;
            }

            if ($resolvedStaffId === null || ! in_array((int) $resolvedStaffId, $slot->staffIds, true)) {
                throw new SlotUnavailableException;
            }

            if ($this->hasBookingOverlap($tenant, $service, (int) $resolvedStaffId, $localStart)) {
                throw new SlotUnavailableException;
            }

            $customer = Customer::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                [
                    'tenant_id' => $tenant->getKey(),
                    'phone' => $phone,
                ],
                [
                    'name' => $customerData['name'],
                    'email' => $customerData['email'] ?? null,
                ],
            );

            $booking = Booking::create([
                'tenant_id' => $tenant->getKey(),
                'service_id' => $service->getKey(),
                'staff_id' => $resolvedStaffId,
                'customer_id' => $customer->getKey(),
                'starts_at' => $localStart->utc(),
                'ends_at' => $localStart->addMinutes($service->duration_minutes)->utc(),
                'status' => BookingStatus::Confirmed,
                'notes' => $notes,
                'price' => $service->price,
            ]);

            return [$booking, $customer];
        });

        $this->sendConfirmation($booking);

        return $booking;
    }

    /**
     * Apply an allowed status transition.
     *
     * @throws BookingRuleException
     */
    public function transition(Booking $booking, BookingStatus $target, ?string $reason = null): Booking
    {
        if (! $booking->canTransitionTo($target)) {
            throw new BookingRuleException(sprintf(
                'تغییر وضعیت از «%s» به «%s» مجاز نیست.',
                $booking->status->label(),
                $target->label(),
            ));
        }

        $wasPending = $booking->status === BookingStatus::Pending;
        $booking->status = $target;

        if ($target === BookingStatus::Cancelled) {
            $booking->cancelled_at = now();
            $booking->cancel_reason = $reason;
        }

        $booking->save();

        if ($target === BookingStatus::Confirmed && $wasPending) {
            $this->sendConfirmation($booking);
        }

        return $booking;
    }

    /**
     * Cancel a booking. The slot becomes bookable again immediately because
     * cancelled bookings are excluded from the availability query.
     */
    public function cancel(Booking $booking, ?string $reason = null, bool $enforceCustomerWindow = false): Booking
    {
        if ($enforceCustomerWindow) {
            $tenant = Tenant::query()->findOrFail($booking->tenant_id);
            $deadline = $booking->startsAtIn($tenant->timezone())
                ->subHours($tenant->cancellationWindowHours());

            if (CarbonImmutable::now($tenant->timezone())->greaterThan($deadline)) {
                throw new BookingRuleException(sprintf(
                    'لغو نوبت تا %d ساعت قبل از زمان نوبت امکان‌پذیر است.',
                    $tenant->cancellationWindowHours(),
                ));
            }
        }

        return $this->transition($booking, BookingStatus::Cancelled, $reason);
    }

    /**
     * @param  array{name?: string, phone?: string, email?: ?string}  $customerData
     */
    private function assertRequestIsValid(
        Tenant $tenant,
        Service $service,
        ?Staff $staff,
        CarbonImmutable $localStart,
        CarbonImmutable $now,
        array $customerData,
    ): void {
        if ((int) $service->tenant_id !== (int) $tenant->getKey()) {
            throw new BookingRuleException('خدمت انتخاب‌شده متعلق به این کسب‌وکار نیست.');
        }

        if (! $service->is_active) {
            throw new BookingRuleException('این خدمت در حال حاضر فعال نیست.');
        }

        if ($staff !== null) {
            if ((int) $staff->tenant_id !== (int) $tenant->getKey()) {
                throw new BookingRuleException('متخصص انتخاب‌شده متعلق به این کسب‌وکار نیست.');
            }

            if (! $staff->is_active) {
                throw new BookingRuleException('این متخصص در حال حاضر فعال نیست.');
            }

            if (! $service->isPerformedBy($staff)) {
                throw new BookingRuleException('این متخصص این خدمت را ارائه نمی‌دهد.');
            }
        }

        if ($localStart->lessThan($now)) {
            throw new BookingRuleException('زمان انتخاب‌شده گذشته است.');
        }

        $horizonEnd = $now->startOfDay()->addDays($tenant->bookingHorizonDays())->endOfDay();

        if ($localStart->greaterThan($horizonEnd)) {
            throw new BookingRuleException('زمان انتخاب‌شده خارج از بازهٔ رزرو است.');
        }

        if (trim((string) ($customerData['name'] ?? '')) === '') {
            throw new BookingRuleException('نام مشتری الزامی است.');
        }

        $phone = Phone::normalize($customerData['phone'] ?? '');

        if (! Phone::isIranianMobile($phone)) {
            throw new BookingRuleException('شمارهٔ همراه باید با ۰۹ شروع شود و ۱۱ رقم باشد.');
        }
    }

    private function findSlot(
        Tenant $tenant,
        Service $service,
        ?Staff $staff,
        CarbonImmutable $localStart,
    ): ?AvailableSlot {
        foreach ($this->availability->slots($tenant, $service, $staff, $localStart) as $slot) {
            if ($slot->startsAt->equalTo($localStart)) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Final defensive overlap check inside the transaction. This includes both
     * the candidate's buffer and the buffer of the existing booking.
     */
    private function hasBookingOverlap(
        Tenant $tenant,
        Service $service,
        int $staffId,
        CarbonImmutable $localStart,
    ): bool {
        $occupiedStart = $localStart->utc();
        $occupiedEnd = $localStart->addMinutes($service->duration_minutes + $service->buffer_minutes)->utc();

        $existing = Booking::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->where('staff_id', $staffId)
            ->whereIn('status', BookingStatus::blockingValues())
            ->where('starts_at', '<', $occupiedEnd)
            ->where('ends_at', '>', $occupiedStart->subDay())
            ->get(['id', 'service_id', 'starts_at', 'ends_at']);

        $buffers = Service::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->pluck('buffer_minutes', 'id');

        foreach ($existing as $booking) {
            $existingEnd = CarbonImmutable::instance($booking->ends_at)
                ->addMinutes((int) ($buffers[$booking->service_id] ?? 0));

            if ($occupiedStart->lessThan($existingEnd)
                && $occupiedEnd->greaterThan(CarbonImmutable::instance($booking->starts_at))) {
                return true;
            }
        }

        return false;
    }

    private function sendConfirmation(Booking $booking): void
    {
        $customer = Customer::withoutGlobalScope(TenantScope::class)->find($booking->customer_id);

        if ($customer === null) {
            return;
        }

        try {
            $customer->notify(new BookingConfirmed($booking));
        } catch (Throwable $exception) {
            // A notification must never roll back a committed booking.
            report($exception);
        }
    }
}
