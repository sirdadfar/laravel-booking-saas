<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class TenantResolutionHttpTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private Tenant $second;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();
        $this->second = Tenant::factory()->create(['name' => 'کلینیک مهر', 'slug' => 'mehr']);
    }

    public function test_the_home_page_lists_tenants_when_none_is_resolved(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('کسب‌وکارهای نمونه');
        $response->assertSee($this->tenant->name);
        $response->assertSee('کلینیک مهر');
    }

    public function test_the_x_tenant_header_resolves_a_tenant_on_the_home_page(): void
    {
        $response = $this->withHeader('X-Tenant', 'rose')->get('/');

        $response->assertOk();
        $response->assertSee($this->tenant->name);
        $response->assertDontSee('کلینیک مهر');
        $this->assertSame('header', app(TenantContext::class)->source());
    }

    public function test_a_subdomain_resolves_a_tenant(): void
    {
        $response = $this->get('http://rose.localhost/');

        $response->assertOk();
        $response->assertSee($this->tenant->name);
        $this->assertSame('subdomain', app(TenantContext::class)->source());
    }

    public function test_a_tenant_subdomain_hides_the_landing_page(): void
    {
        $this->get('http://rose.localhost/')->assertDontSee('کسب‌وکارهای نمونه');
    }

    public function test_the_path_wins_over_the_header(): void
    {
        $response = $this->withHeader('X-Tenant', 'mehr')->get('/rose');

        $response->assertOk();
        $response->assertSee($this->tenant->name);
        $response->assertDontSee('کلینیک مهر');
        $this->assertSame('path', app(TenantContext::class)->source());
    }

    public function test_an_unknown_header_value_is_rejected_instead_of_silently_falling_back(): void
    {
        $this->withHeader('X-Tenant', 'ghost')->get('/')->assertNotFound();
    }

    public function test_an_unknown_subdomain_returns_404(): void
    {
        $this->get('http://ghost.localhost/')->assertNotFound();
    }
}
