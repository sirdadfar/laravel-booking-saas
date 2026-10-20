<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\File;

/**
 * A deliberately boring SMS driver: it appends the message to a log file.
 *
 * This keeps the project runnable with zero credentials and zero cost, while
 * the notification still goes through Laravel's real notification pipeline
 * (so swapping in Kavenegar/SMS.ir later is a one-class change).
 */
class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = (string) $notification->toSms($notifiable);
        $to = (string) ($notifiable->routeNotificationFor('sms', $notification)
            ?? $notifiable->routeNotificationForSms()
            ?? '');

        if ($message === '' || $to === '') {
            return;
        }

        $path = (string) config('sms.log_path');
        File::ensureDirectoryExists(dirname($path));

        File::append($path, sprintf(
            '[%s] to=%s %s%s',
            now()->toIso8601String(),
            $to,
            $message,
            PHP_EOL,
        ));
    }
}
