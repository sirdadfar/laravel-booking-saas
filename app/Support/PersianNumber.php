<?php

namespace App\Support;

/**
 * Display-only number formatting for the Persian interface.
 *
 * Money maths and storage stay integer/Latin (see `bookings.price` and the
 * commission/buffer calculations); this helper only converts what a
 * Persian-speaking visitor actually reads, so one page never mixes
 * "۳۰ دقیقه" with "30 دقیقه".
 */
final class PersianNumber
{
    private const DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /**
     * Convert ASCII digits to Persian digits, leaving everything else alone
     * (separators, slashes, colons, currency words).
     */
    public static function digits(int|float|string|null $value): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            self::DIGITS,
            (string) ($value ?? '')
        );
    }

    /**
     * Thousands-separated count or amount: 1250000 → ۱,۲۵۰,۰۰۰
     */
    public static function grouped(int|float|string|null $value): string
    {
        return self::digits(number_format((float) ($value ?? 0), 0, '.', ','));
    }

    /**
     * Amount with its currency word: 250000 → ۲۵۰,۰۰۰ تومان
     */
    public static function money(int|float|string|null $value): string
    {
        return self::grouped($value).' تومان';
    }
}
