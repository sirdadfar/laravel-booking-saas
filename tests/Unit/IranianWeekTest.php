<?php

namespace Tests\Unit;

use App\Support\IranianWeek;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class IranianWeekTest extends TestCase
{
    public function test_saturday_is_day_zero_and_friday_is_day_six(): void
    {
        $this->assertSame(0, IranianWeek::weekday(CarbonImmutable::parse('2026-05-02'))); // Saturday
        $this->assertSame(1, IranianWeek::weekday(CarbonImmutable::parse('2026-05-03'))); // Sunday
        $this->assertSame(6, IranianWeek::weekday(CarbonImmutable::parse('2026-05-08'))); // Friday
    }

    public function test_labels_cover_the_whole_week(): void
    {
        $this->assertSame('شنبه', IranianWeek::label(0));
        $this->assertSame('جمعه', IranianWeek::label(6));
        $this->assertSame('—', IranianWeek::label(7));
        $this->assertCount(7, IranianWeek::LABELS);
    }

    public function test_the_week_starts_on_saturday(): void
    {
        $monday = CarbonImmutable::parse('2026-05-04 15:30:00');

        $this->assertSame('2026-05-02 00:00:00', IranianWeek::startOfWeek($monday)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-08 23:59:59', IranianWeek::endOfWeek($monday)->format('Y-m-d H:i:s'));
        $this->assertSame('دوشنبه', IranianWeek::dayName($monday));
    }

    public function test_a_friday_belongs_to_the_week_that_started_the_saturday_before(): void
    {
        $friday = CarbonImmutable::parse('2026-05-08 10:00:00');

        $this->assertSame('2026-05-02', IranianWeek::startOfWeek($friday)->format('Y-m-d'));
    }
}
