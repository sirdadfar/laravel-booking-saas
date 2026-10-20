<?php

namespace Tests\Feature;

use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class SmsChannelTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    public function test_the_confirmation_is_written_to_the_sms_log(): void
    {
        $path = storage_path('framework/testing/sms-'.uniqid('', true).'.log');

        config(['sms.log_path' => $path]);

        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();

        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);

        $booking = app(BookingService::class)->book(
            $this->tenant,
            $service,
            $staff,
            $this->timeAt('2026-05-05 10:00'),
            ['name' => 'سحر موسوی', 'phone' => '۰۹۱۲۱۲۳۴۵۶۷'],
        );

        $this->assertFileExists($path);

        $log = File::get($path);

        $this->assertStringContainsString('to=09121234567', $log);
        $this->assertStringContainsString($booking->code, $log);
        $this->assertStringContainsString($this->tenant->name, $log);

        File::delete($path);
    }
}
