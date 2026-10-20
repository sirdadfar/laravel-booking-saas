<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use PHPUnit\Framework\TestCase;

/**
 * The badge modifier is a string contract between the enum and the
 * stylesheet. A typo here renders an unstyled badge on the public
 * confirmation page and nobody notices until a customer does.
 */
class BookingStatusBadgeTest extends TestCase
{
    private function stylesheet(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/public/css/app.css');
    }

    public function test_pending_uses_the_modifier_the_stylesheet_defines(): void
    {
        $css = $this->stylesheet();

        $this->assertSame('badge-warn', BookingStatus::Pending->badgeClass());
        $this->assertStringContainsString('.badge-warn {', $css);
        $this->assertStringNotContainsString('.badge-warning {', $css);
    }

    public function test_every_status_maps_to_a_stylesheet_rule(): void
    {
        $css = $this->stylesheet();

        foreach (BookingStatus::cases() as $status) {
            $this->assertMatchesRegularExpression(
                '/\.'.preg_quote($status->badgeClass(), '/').'\s*\{/',
                $css,
                sprintf('No CSS rule for %s (%s).', $status->value, $status->badgeClass()),
            );
        }
    }
}
