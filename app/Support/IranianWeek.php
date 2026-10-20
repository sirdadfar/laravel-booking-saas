<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class IranianWeek
{
    public const LABELS = [
        0 => 'شنبه',
        1 => 'یکشنبه',
        2 => 'دوشنبه',
        3 => 'سه‌شنبه',
        4 => 'چهارشنبه',
        5 => 'پنجشنبه',
        6 => 'جمعه',
    ];

    /**
     * Convert a Carbon date to the Iranian week index (Saturday = 0).
     */
    public static function weekday(CarbonInterface $date): int
    {
        return ($date->dayOfWeek + 1) % 7;
    }

    public static function label(int $weekday): string
    {
        return self::LABELS[$weekday] ?? '—';
    }

    public static function dayName(CarbonInterface $date): string
    {
        return self::label(self::weekday($date));
    }

    /**
     * Saturday 00:00 of the week containing the given date.
     */
    public static function startOfWeek(CarbonInterface $date): CarbonInterface
    {
        return $date->copy()->startOfDay()->subDays(self::weekday($date));
    }

    public static function endOfWeek(CarbonInterface $date): CarbonInterface
    {
        return self::startOfWeek($date)->addDays(6)->endOfDay();
    }
}
