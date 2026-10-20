<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار تأیید',
            self::Confirmed => 'تأیید شده',
            self::Cancelled => 'لغو شده',
            self::Completed => 'انجام شده',
            self::NoShow => 'عدم حضور',
        };
    }

    /**
     * CSS modifier for the shared `.badge` component in `public/css/app.css`.
     * Keep this in sync with the stylesheet: an unknown modifier silently
     * renders an unstyled badge (this is covered by BookingStatusBadgeTest).
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge-warn',
            self::Confirmed => 'badge-success',
            self::Cancelled => 'badge-muted',
            self::Completed => 'badge-info',
            self::NoShow => 'badge-danger',
        };
    }

    /**
     * Booking statuses that still occupy the staff calendar.
     *
     * Cancelled bookings are deliberately excluded so their slot becomes
     * bookable again. Completed and no-show bookings stay "blocking" for
     * historical integrity, but they are normally in the past and therefore
     * filtered out by the availability engine's past-slot rule.
     *
     * @return array<int, string>
     */
    public static function blockingValues(): array
    {
        return [
            self::Pending->value,
            self::Confirmed->value,
            self::Completed->value,
            self::NoShow->value,
        ];
    }

    public function isBlocking(): bool
    {
        return in_array($this->value, self::blockingValues(), true);
    }

    public function canTransitionTo(self $target): bool
    {
        if ($target === $this) {
            return false;
        }

        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($target, [self::Completed, self::Cancelled, self::NoShow], true),
            self::Cancelled, self::Completed, self::NoShow => false,
        };
    }
}
