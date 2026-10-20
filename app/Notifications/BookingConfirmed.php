<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification
{
    use Queueable;

    public function __construct(public readonly Booking $booking) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'sms'];

        // A customer without an email still gets the SMS; mail is skipped so
        // the log driver does not fill up with undeliverable messages.
        if (blank($notifiable->email ?? null)) {
            $channels = array_values(array_diff($channels, ['mail']));
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->tenant();
        $startsAt = $this->booking->startsAtIn($tenant->timezone());

        return (new MailMessage)
            ->subject(sprintf('نوبت شما در %s ثبت شد', $tenant->name))
            ->greeting(sprintf('%s عزیز،', $this->booking->customer?->name ?? 'سلام'))
            ->line(sprintf('کد پیگیری نوبت شما: %s', $this->booking->code))
            ->line(sprintf('خدمت: %s', $this->booking->service?->name ?? '—'))
            ->line(sprintf('زمان: %s ساعت %s', $startsAt->format('Y/m/d'), $startsAt->format('H:i')))
            ->line(sprintf('آدرس: %s', $tenant->address ?: '—'))
            ->line(sprintf('برای لغو نوبت تا %d ساعت قبل از زمان نوبت فرصت دارید.', $tenant->cancellationWindowHours()))
            ->salutation($tenant->name);
    }

    public function toSms(object $notifiable): string
    {
        $tenant = $this->tenant();
        $startsAt = $this->booking->startsAtIn($tenant->timezone());

        return sprintf(
            '%s: نوبت شما %s ساعت %s ثبت شد. کد پیگیری: %s',
            $tenant->name,
            $startsAt->format('Y/m/d'),
            $startsAt->format('H:i'),
            $this->booking->code,
        );
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->findOrFail($this->booking->tenant_id);
    }
}
