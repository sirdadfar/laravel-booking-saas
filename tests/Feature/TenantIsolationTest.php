<?php

namespace Tests\Feature;

use App\Exceptions\TenantNotResolvedException;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    public function test_tenant_models_cannot_be_created_without_a_context(): void
    {
        $this->expectException(TenantNotResolvedException::class);

        Service::query()->create([
            'name' => 'خدمت بی‌صاحب',
            'duration_minutes' => 30,
            'price' => 1000,
            'is_active' => true,
        ]);
    }

    public function test_queries_fail_closed_when_no_tenant_is_resolved(): void
    {
        $this->bootTenant();
        $service = $this->makeService();
        $staff = $this->makeStaff([$service]);
        $this->makeBooking($service, $staff, '2026-05-05 10:00');

        app(TenantContext::class)->forget();

        $this->assertSame(0, Service::query()->count());
        $this->assertSame(0, Staff::query()->count());
        $this->assertSame(0, Booking::query()->count());
    }

    public function test_a_tenant_only_sees_its_own_rows(): void
    {
        $this->bootTenant();
        $mine = $this->makeService(30, 0, ['name' => 'خدمت من']);

        $other = Tenant::factory()->create();
        $context = app(TenantContext::class);
        $theirs = $context->runFor($other, fn () => Service::factory()->forTenant($other)->create(['name' => 'خدمت دیگران']));

        $this->assertSame([$mine->id], Service::query()->pluck('id')->all());
        $this->assertNull(Service::query()->find($theirs->id));

        $context->set($other);
        $this->assertSame([$theirs->id], Service::query()->pluck('id')->all());
        $this->assertNull(Service::query()->find($mine->id));
    }

    public function test_switching_the_context_switches_the_visibility(): void
    {
        $this->bootTenant();
        $this->makeService(30, 0, ['name' => 'آرایش']);

        $other = Tenant::factory()->create();
        $context = app(TenantContext::class);
        $context->runFor($other, fn () => Service::factory()->forTenant($other)->create(['name' => 'دندان']));

        $context->set($other);

        $this->assertSame(['دندان'], Service::query()->pluck('name')->all());

        $context->set($this->tenant);

        $this->assertSame(['آرایش'], Service::query()->pluck('name')->all());
    }

    public function test_tenant_id_is_assigned_from_the_context_on_create(): void
    {
        $this->bootTenant();
        $service = $this->makeService();

        $this->assertSame($this->tenant->id, $service->tenant_id);
    }

    public function test_tenant_id_cannot_be_changed_after_creation(): void
    {
        $this->bootTenant();
        $service = $this->makeService();
        $other = Tenant::factory()->create();

        $this->expectException(LogicException::class);

        $service->tenant_id = $other->id;
        $service->save();
    }

    public function test_relations_are_resolved_inside_the_context(): void
    {
        $this->bootTenant();
        $service = $this->makeService(30);
        $staff = $this->makeStaff([$service]);
        $booking = $this->makeBooking($service, $staff, '2026-05-05 10:00');

        $fresh = Booking::query()->with(['service', 'staff', 'customer'])->findOrFail($booking->id);

        $this->assertSame($service->id, $fresh->service->id);
        $this->assertSame($staff->id, $fresh->staff->id);
        $this->assertSame($this->tenant->id, $fresh->customer->tenant_id);
    }

    public function test_run_for_restores_the_previous_context(): void
    {
        $this->bootTenant();
        $original = $this->tenant;
        $other = Tenant::factory()->create();
        $context = app(TenantContext::class);

        $result = $context->runFor($other, fn () => $context->id());

        $this->assertSame($other->id, $result);
        $this->assertSame($original->id, $context->id());
    }
}
