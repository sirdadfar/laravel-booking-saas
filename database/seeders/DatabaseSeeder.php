<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Throwable;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Tenant::query()->where('slug', 'rose')->exists()) {
            $this->command?->warn('دادهٔ نمایشی از قبل وجود دارد؛ seed دوباره اجرا نشد.');

            return;
        }

        $context = app(TenantContext::class);

        $rose = Tenant::query()->create([
            'name' => 'آرایشگاه رز',
            'slug' => 'rose',
            'timezone' => 'Asia/Tehran',
            'phone' => '02188776655',
            'address' => 'تهران، خیابان ولیعصر، کوچهٔ بهار، پلاک ۱۲',
            'is_active' => true,
            'settings' => [
                'slot_granularity_minutes' => 30,
                'booking_horizon_days' => 30,
                'cancellation_window_hours' => 6,
            ],
        ]);

        $mehr = Tenant::query()->create([
            'name' => 'کلینیک دندان‌پزشکی مهر',
            'slug' => 'mehr',
            'timezone' => 'Asia/Kabul',
            'phone' => '0700123456',
            'address' => 'کابل، شهر نو، خیابان ۱۰، ساختمان مهر',
            'is_active' => true,
            'settings' => [
                'slot_granularity_minutes' => 20,
                'booking_horizon_days' => 21,
                'cancellation_window_hours' => 24,
            ],
        ]);

        $context->runFor($rose, fn () => $this->seedRose($rose));
        $context->runFor($mehr, fn () => $this->seedMehr($mehr));

        User::query()->create([
            'tenant_id' => $rose->id,
            'name' => 'مدیر آرایشگاه رز',
            'email' => 'admin@rose.test',
            'password' => 'password',
            'is_platform_admin' => false,
        ]);

        User::query()->create([
            'tenant_id' => $mehr->id,
            'name' => 'مدیر کلینیک مهر',
            'email' => 'admin@mehr.test',
            'password' => 'password',
            'is_platform_admin' => false,
        ]);

        User::query()->create([
            'tenant_id' => null,
            'name' => 'مدیر پلتفرم',
            'email' => 'admin@nobatyar.test',
            'password' => 'password',
            'is_platform_admin' => true,
        ]);
    }

    private function seedRose(Tenant $tenant): void
    {
        $haircut = Service::query()->create([
            'name' => 'کوتاهی مو',
            'duration_minutes' => 45,
            'buffer_minutes' => 10,
            'price' => 450000,
            'is_active' => true,
            'color' => '#0f766e',
        ]);

        $color = Service::query()->create([
            'name' => 'رنگ و مش',
            'duration_minutes' => 90,
            'buffer_minutes' => 15,
            'price' => 1800000,
            'is_active' => true,
            'color' => '#b45309',
        ]);

        $beard = Service::query()->create([
            'name' => 'اصلاح ریش',
            'duration_minutes' => 30,
            'buffer_minutes' => 5,
            'price' => 250000,
            'is_active' => true,
            'color' => '#1d4ed8',
        ]);

        $maryam = Staff::query()->create(['name' => 'مریم رستمی', 'phone' => '09121110001', 'is_active' => true]);
        $sara = Staff::query()->create(['name' => 'سارا کاظمی', 'phone' => '09121110002', 'is_active' => true]);
        $reza = Staff::query()->create(['name' => 'رضا محمدی', 'phone' => '09121110003', 'is_active' => true]);

        $maryam->services()->sync([$haircut->id, $color->id, $beard->id]);
        $sara->services()->sync([$color->id, $haircut->id]);
        $reza->services()->sync([$beard->id, $haircut->id]);

        $this->schedules($maryam, ['09:00-13:00', '15:00-19:00'], closedWeekdays: []);
        $this->schedules($sara, ['10:00-14:00', '16:00-20:00'], closedWeekdays: [0, 6]);
        $this->schedules($reza, ['12:00-20:00'], closedWeekdays: [6]);

        $this->seedBookings($tenant, [
            [$haircut, 'سحر موسوی', '09121230001'],
            [$color, 'نگار احمدی', '09121230002'],
            [$beard, 'امیر کریمی', '09121230003'],
        ]);
    }

    private function seedMehr(Tenant $tenant): void
    {
        $cleaning = Service::query()->create([
            'name' => 'جرم‌گیری و بروساژ',
            'duration_minutes' => 45,
            'buffer_minutes' => 15,
            'price' => 900000,
            'is_active' => true,
            'color' => '#0ea5e9',
        ]);

        $composite = Service::query()->create([
            'name' => 'کامپوزیت دندان',
            'duration_minutes' => 60,
            'buffer_minutes' => 10,
            'price' => 3500000,
            'is_active' => true,
            'color' => '#7c3aed',
        ]);

        $consult = Service::query()->create([
            'name' => 'مشاورهٔ درمان',
            'duration_minutes' => 20,
            'buffer_minutes' => 5,
            'price' => 200000,
            'is_active' => true,
            'color' => '#059669',
        ]);

        $niloufar = Staff::query()->create(['name' => 'دکتر نیلوفر احمدی', 'phone' => '07001110001', 'is_active' => true]);
        $saman = Staff::query()->create(['name' => 'دکتر سامان رضایی', 'phone' => '07001110002', 'is_active' => true]);

        $niloufar->services()->sync([$cleaning->id, $composite->id, $consult->id]);
        $saman->services()->sync([$composite->id, $consult->id]);

        // جمعه تعطیل است؛ شنبه تا پنجشنبه دو شیفت.
        $this->schedules($niloufar, ['08:30-12:30', '14:00-18:00'], closedWeekdays: [6]);
        $this->schedules($saman, ['10:00-14:00', '16:00-20:00'], closedWeekdays: [6]);

        // شعبهٔ کابل است، اما مشتریان ایرانی‌اند و شمارهٔ همراه ایرانی دارند
        // (اعتبارسنجی شماره در Phone متمرکز است).
        $this->seedBookings($tenant, [
            [$cleaning, 'زهرا نوری', '09121230101'],
            [$composite, 'مهدی صادقی', '09121230102'],
            [$consult, 'فاطمه حسینی', '09121230103'],
        ]);
    }

    /**
     * @param  array<int, string>  $windows  e.g. ['09:00-13:00', '15:00-19:00']
     * @param  array<int, int>  $closedWeekdays  Iranian weekday indexes (0 = Saturday)
     */
    private function schedules(Staff $staff, array $windows, array $closedWeekdays): void
    {
        foreach (range(0, 6) as $weekday) {
            if (in_array($weekday, $closedWeekdays, true)) {
                continue;
            }

            foreach ($windows as $window) {
                [$start, $end] = explode('-', $window);

                StaffSchedule::query()->create([
                    'staff_id' => $staff->id,
                    'weekday' => $weekday,
                    'start_time' => $start,
                    'end_time' => $end,
                ]);
            }
        }
    }

    /**
     * Walk the booking horizon and book the first free slot for each service.
     * Going through BookingService keeps the demo data subject to exactly the
     * same rules as a real customer request.
     *
     * @param  array<int, array{0: Service, 1: string, 2: string}>  $plan
     */
    private function seedBookings(Tenant $tenant, array $plan): void
    {
        $availability = app(AvailabilityService::class);
        $bookings = app(BookingService::class);
        $timezone = $tenant->timezone();
        $start = CarbonImmutable::now($timezone)->addDay()->startOfDay();

        foreach ($plan as [$service, $customerName, $phone]) {
            $booked = false;

            for ($offset = 0; $offset < 10 && ! $booked; $offset++) {
                $date = $start->addDays($offset);
                $slots = $availability->slots($tenant, $service, null, $date);

                if ($slots === []) {
                    continue;
                }

                try {
                    $bookings->book(
                        $tenant,
                        $service,
                        null,
                        $slots[0]->startsAt,
                        ['name' => $customerName, 'phone' => $phone],
                        'رزرو نمونهٔ داده‌های نمایشی',
                    );

                    $booked = true;
                } catch (Throwable $exception) {
                    // The slot was taken between listing and booking; try the
                    // next day. Keep the reason visible: silently skipping is
                    // how a broken rule turns into "demo data just looks thin".
                    $this->command?->warn(sprintf(
                        'seed: %s / %s در %s ثبت نشد: %s',
                        $tenant->slug,
                        $service->name,
                        $date->toDateString(),
                        $exception->getMessage(),
                    ));
                }
            }
        }

        // One completed visit yesterday, so the dashboard revenue card is not zero.
        $firstService = $plan[0][0];
        $firstStaff = $firstService->staff()->first();

        if ($firstStaff === null) {
            return;
        }

        $customer = Customer::query()->create([
            'name' => 'مشتری نمونه',
            'phone' => '09129990000',
            'email' => null,
        ]);

        $yesterday = CarbonImmutable::now($timezone)->subDay()->setTime(11, 0);

        Booking::query()->create([
            'service_id' => $firstService->id,
            'staff_id' => $firstStaff->id,
            'customer_id' => $customer->id,
            'starts_at' => $yesterday->utc(),
            'ends_at' => $yesterday->addMinutes($firstService->duration_minutes)->utc(),
            'status' => BookingStatus::Completed,
            'notes' => 'نوبت انجام‌شدهٔ نمونه',
            'price' => $firstService->price,
        ]);
    }
}
