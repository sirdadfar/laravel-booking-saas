<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

/**
 * Regression test for the platform overview counters.
 *
 * tenant-owned models carry the fail-closed TenantScope. A platform route has
 * no tenant context, so `withCount()` used to return 0 for every tenant even
 * though the rows existed. The controller opts out of that scope per relation.
 */
class PlatformTenantCountsTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    public function test_the_platform_overview_counts_records_per_tenant(): void
    {
        $this->bootTenant();

        $service = $this->makeService();
        $staff = $this->makeStaff([$service]);
        $this->makeBooking($service, $staff, '2026-05-04 10:00');

        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'owner@rose.test',
        ]);

        $platform = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $response = $this->actingAs($platform)->get('/platform/tenants');
        $response->assertOk();

        $row = collect($response->viewData('tenants')->items())
            ->firstWhere('id', $this->tenant->id);

        $this->assertNotNull($row, 'The seeded tenant must be listed for the platform admin.');
        $this->assertSame(1, $row->services_count);
        $this->assertSame(1, $row->staff_count);
        $this->assertSame(1, $row->bookings_count);
        $this->assertSame(1, $row->users_count);
    }
}
