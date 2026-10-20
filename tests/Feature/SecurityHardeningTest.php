<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_carry_the_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertStringContainsString(
            "frame-ancestors 'none'",
            (string) $response->headers->get('Content-Security-Policy-Report-Only'),
        );
        $this->assertStringContainsString(
            "object-src 'none'",
            (string) $response->headers->get('Content-Security-Policy-Report-Only'),
        );
    }

    public function test_hsts_is_only_sent_on_secure_requests(): void
    {
        $this->assertNull($this->get('/')->headers->get('Strict-Transport-Security'));
        $this->assertNotNull(
            $this->get('https://localhost/')->headers->get('Strict-Transport-Security'),
        );
    }

    public function test_the_platform_admin_flag_cannot_be_mass_assigned(): void
    {
        $user = new User([
            'name' => 'مهاجم',
            'email' => 'attacker@example.test',
            'is_platform_admin' => true,
        ]);

        $tenant = Tenant::factory()->create();

        $this->assertFalse((bool) $user->is_platform_admin);
        $this->assertFalse($user->canAccessTenant($tenant));
    }
}
