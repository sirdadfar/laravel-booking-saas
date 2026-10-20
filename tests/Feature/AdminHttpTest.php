<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class AdminHttpTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private User $admin;

    private Tenant $second;

    private User $secondAdmin;

    private User $platform;

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

        $this->second = Tenant::factory()->create(['name' => 'کلینیک مهر', 'slug' => 'mehr']);

        $this->secondAdmin = User::factory()->create([
            'tenant_id' => $this->second->id,
            'email' => 'admin@mehr.test',
            'password' => 'password',
        ]);

        $this->platform = User::factory()->create([
            'tenant_id' => null,
            'email' => 'root@nobatyar.test',
            'password' => 'password',
            'is_platform_admin' => true,
        ]);
    }

    public function test_a_guest_is_redirected_to_the_login_page(): void
    {
        $this->get('/rose/admin')->assertRedirect('/login');
    }

    public function test_a_tenant_admin_sees_their_dashboard(): void
    {
        $service = $this->makeService();
        $staff = $this->makeStaff([$service]);
        $this->makeBooking($service, $staff, '2026-05-04 10:00');

        $response = $this->actingAs($this->admin)->get('/rose/admin');

        $response->assertOk();
        $response->assertSee('داشبورد');
        $response->assertSee($this->tenant->name);
    }

    public function test_a_tenant_admin_cannot_open_another_tenants_dashboard(): void
    {
        $this->actingAs($this->admin)->get('/mehr/admin')->assertForbidden();
    }

    public function test_a_platform_admin_can_open_any_tenant_dashboard(): void
    {
        $this->actingAs($this->platform)->get('/mehr/admin')->assertOk();
    }

    public function test_a_tenant_admin_can_create_a_service(): void
    {
        $staff = $this->makeStaff([], attributes: ['name' => 'مریم']);

        $this->actingAs($this->admin)->post('/rose/admin/services', [
            'name' => 'رنگ مو',
            'duration_minutes' => 90,
            'buffer_minutes' => 15,
            'price' => 1500000,
            'color' => '#b45309',
            'is_active' => '1',
            'staff_ids' => [$staff->id],
        ])->assertRedirect();

        $service = Service::query()->where('name', 'رنگ مو')->firstOrFail();

        $this->assertSame($this->tenant->id, $service->tenant_id);
        $this->assertSame(90, $service->duration_minutes);
        $this->assertSame(15, $service->buffer_minutes);
        $this->assertTrue($service->is_active);
        $this->assertSame([$staff->id], $service->staff->pluck('id')->all());
    }

    public function test_a_tenant_admin_cannot_touch_another_tenants_service(): void
    {
        $foreign = app(TenantContext::class)->runFor(
            $this->second,
            fn () => Service::factory()->forTenant($this->second)->create(['name' => 'خدمت دیگران']),
        );

        $this->actingAs($this->admin)
            ->put('/rose/admin/services/'.$foreign->id, [
                'name' => 'هک',
                'duration_minutes' => 30,
                'price' => 1000,
            ])
            ->assertNotFound();

        $this->assertSame('خدمت دیگران', $foreign->fresh()->name);
    }

    public function test_a_service_with_bookings_cannot_be_deleted(): void
    {
        $service = $this->makeService();
        $staff = $this->makeStaff([$service]);
        $this->makeBooking($service, $staff, '2026-05-04 10:00');

        $this->actingAs($this->admin)
            ->delete('/rose/admin/services/'.$service->id)
            ->assertSessionHasErrors('service');

        $this->assertNotNull($service->fresh());
    }

    public function test_a_tenant_admin_can_add_a_staff_member_with_two_shift_windows(): void
    {
        $service = $this->makeService();

        $this->actingAs($this->admin)->post('/rose/admin/staff', [
            'name' => 'سارا کاظمی',
            'phone' => '09121110002',
            'is_active' => '1',
            'service_ids' => [$service->id],
            'shifts' => [
                2 => '09:00-13:00, 15:00-19:00',
            ],
        ])->assertRedirect();

        $staff = Staff::query()->where('name', 'سارا کاظمی')->firstOrFail();

        $this->assertSame($this->tenant->id, $staff->tenant_id);
        $this->assertSame([$service->id], $staff->services->pluck('id')->all());
        $this->assertSame(2, StaffSchedule::query()->where('staff_id', $staff->id)->count());
        $this->assertSame(
            ['09:00-13:00', '15:00-19:00'],
            $staff->schedules()->orderBy('start_time')->get()
                ->map(fn (StaffSchedule $schedule): string => $schedule->start_time.'-'.$schedule->end_time)
                ->all(),
        );
    }

    public function test_an_invalid_shift_window_is_rejected(): void
    {
        $this->actingAs($this->admin)->post('/rose/admin/staff', [
            'name' => 'سارا کاظمی',
            'shifts' => [2 => '25:00-13:00'],
        ])->assertSessionHasErrors('shifts.2');

        $this->assertSame(0, Staff::query()->count());
    }

    public function test_a_shift_that_ends_before_it_starts_is_rejected(): void
    {
        $this->actingAs($this->admin)->post('/rose/admin/staff', [
            'name' => 'سارا کاظمی',
            'shifts' => [2 => '19:00-09:00'],
        ])->assertSessionHasErrors('shifts.2');
    }

    public function test_a_tenant_admin_can_confirm_a_pending_booking(): void
    {
        $service = $this->makeService();
        $staff = $this->makeStaff([$service]);
        $booking = $this->makeBooking($service, $staff, '2026-05-05 10:00', status: BookingStatus::Pending);

        $this->actingAs($this->admin)
            ->post('/rose/admin/bookings/'.$booking->id.'/status', ['status' => 'confirmed'])
            ->assertRedirect();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_an_invalid_transition_is_refused(): void
    {
        $service = $this->makeService();
        $staff = $this->makeStaff([$service]);
        $booking = $this->makeBooking($service, $staff, '2026-05-05 10:00');

        $this->actingAs($this->admin)
            ->postJson('/rose/admin/bookings/'.$booking->id.'/status', ['status' => 'pending'])
            ->assertStatus(422);

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_the_platform_tenant_list_is_only_for_platform_admins(): void
    {
        $this->actingAs($this->admin)->get('/platform/tenants')->assertForbidden();

        $this->actingAs($this->platform)->get('/platform/tenants')
            ->assertOk()
            ->assertSee($this->tenant->name)
            ->assertSee('کلینیک مهر');
    }

    public function test_the_login_flow_redirects_each_role_to_the_right_panel(): void
    {
        $this->post('/login', ['email' => 'admin@rose.test', 'password' => 'password'])
            ->assertRedirect('/rose/admin');

        $this->post('/logout');

        $this->post('/login', ['email' => 'root@nobatyar.test', 'password' => 'password'])
            ->assertRedirect('/platform/tenants');
    }

    public function test_a_wrong_password_does_not_log_in(): void
    {
        $this->from('/login')
            ->post('/login', ['email' => 'admin@rose.test', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
