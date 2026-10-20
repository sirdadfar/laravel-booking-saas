<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function normalisationCases(): array
    {
        return [
            'plain' => ['09123456789', '09123456789'],
            'persian digits' => ['۰۹۱۲۳۴۵۶۷۸۹', '09123456789'],
            'arabic-indic digits' => ['٠٩١٢٣٤٥٦٧٨٩', '09123456789'],
            'spaces' => ['0912 345 6789', '09123456789'],
            'dashes and dots' => ['0912-345.6789', '09123456789'],
            'parentheses' => ['(0912) 345-6789', '09123456789'],
            'surrounding space' => ['  09123456789  ', '09123456789'],
        ];
    }

    #[DataProvider('normalisationCases')]
    public function test_it_normalises_digits_and_separators(string $input, string $expected): void
    {
        $this->assertSame($expected, Phone::normalize($input));
    }

    public function test_null_becomes_an_empty_string(): void
    {
        $this->assertSame('', Phone::normalize(null));
    }

    public function test_it_accepts_only_eleven_digit_numbers_starting_with_09(): void
    {
        $this->assertTrue(Phone::isIranianMobile('09123456789'));
        $this->assertTrue(Phone::isIranianMobile(Phone::normalize('۰۹۱۲۳۴۵۶۷۸۹')));

        $this->assertFalse(Phone::isIranianMobile('9123456789'));
        $this->assertFalse(Phone::isIranianMobile('08123456789'));
        $this->assertFalse(Phone::isIranianMobile('0912345678'));
        $this->assertFalse(Phone::isIranianMobile('091234567890'));
        $this->assertFalse(Phone::isIranianMobile(''));
    }
}
