<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

/**
 * staff_service has no tenant_id column, so the only thing that keeps the
 * pivot clean is validating the submitted ids against the current tenant.
 */
class CrossTenantPivotTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private User $admin;

    private Tenant $other;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-05-04 08:00:00', 'Asia/Tehran'));
        $this->bootTenant();

        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'admin@rose.test',
            'password' => 'password',
        ]);

        $this->other = Tenant::factory()->create(['name' => 'کلینیک مهر', 'slug' => 'mehr']);
    }

    public function test_a_service_cannot_be_linked_to_another_tenants_staff(): void
    {
        $foreign = app(TenantContext::class)->runFor(
            $this->other,
            fn () => Staff::factory()->forTenant($this->other)->create(['name' => 'متخصص دیگران']),
        );

        $this->actingAs($this->admin)
            ->post('/rose/admin/services', [
                'name' => 'خدمت من',
                'duration_minutes' => 30,
                'price' => 1000,
                'staff_ids' => [$foreign->id],
            ])
            ->assertSessionHasErrors('staff_ids.0');

        $this->assertDatabaseMissing('staff_service', ['staff_id' => $foreign->id]);
        $this->assertDatabaseMissing('services', ['name' => 'خدمت من']);
    }

    public function test_a_staff_member_cannot_be_linked_to_another_tenants_service(): void
    {
        $foreign = app(TenantContext::class)->runFor(
            $this->other,
            fn () => Service::factory()->forTenant($this->other)->create(['name' => 'خدمت دیگران']),
        );

        $this->actingAs($this->admin)
            ->post('/rose/admin/staff', [
                'name' => 'عضو من',
                'service_ids' => [$foreign->id],
            ])
            ->assertSessionHasErrors('service_ids.0');

        $this->assertDatabaseMissing('staff_service', ['service_id' => $foreign->id]);
        $this->assertDatabaseMissing('staff', ['name' => 'عضو من']);
    }

    public function test_a_cross_tenant_booking_cannot_be_status_changed(): void
    {
        $foreign = app(TenantContext::class)->runFor(
            $this->other,
            fn () => Booking::factory()->forTenant($this->other)->create(),
        );

        $this->actingAs($this->admin)
            ->post('/rose/admin/bookings/'.$foreign->id.'/status', ['status' => 'cancelled'])
            ->assertNotFound();

        $this->assertSame(
            BookingStatus::Confirmed,
            Booking::withoutGlobalScope(TenantScope::class)->whereKey($foreign->id)->value('status'),
        );
    }
}
