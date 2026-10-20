<?php

namespace App\Support;

final class Phone
{
    /**
     * Normalize Persian/Arabic digits and remove spaces, dashes and dots so
     * that "۰۹۱۲ ۳۴۵ ۶۷۸۹" becomes "09123456789".
     */
    public static function normalize(?string $phone): string
    {
        $phone = trim((string) $phone);

        $phone = strtr($phone, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/[\s\-\.\(\)]+/u', '', $phone) ?? '';
    }

    public static function isIranianMobile(string $phone): bool
    {
        return (bool) preg_match('/^09\d{9}$/', $phone);
    }
}
