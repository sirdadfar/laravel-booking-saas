<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\TenantContext;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly AvailabilityService $availability,
        private readonly BookingService $bookings,
    ) {}

    public function show(Request $request): View
    {
        $tenant = $this->context->requireTenant();

        return view('public.booking', [
            'tenant' => $tenant,
            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'staff' => Staff::query()
                ->active()
                ->with('services')
                ->orderBy('name')
                ->get(),
            'today' => CarbonImmutable::now($tenant->timezone())->toDateString(),
            'horizonEnd' => CarbonImmutable::now($tenant->timezone())
                ->addDays($tenant->bookingHorizonDays())
                ->toDateString(),
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $tenant = $this->context->requireTenant();

        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'staff_id' => ['nullable', 'integer'],
        ]);

        $service = Service::query()->where('is_active', true)->findOrFail($data['service_id']);
        $staff = isset($data['staff_id']) ? Staff::query()->active()->findOrFail($data['staff_id']) : null;

        $date = CarbonImmutable::createFromFormat('Y-m-d', $data['date'], $tenant->timezone());

        if ($date === false) {
            throw ValidationException::withMessages(['date' => 'تاریخ نامعتبر است.']);
        }

        $slots = $this->availability->slots($tenant, $service, $staff, $date->startOfDay());

        return response()->json([
            'date' => $date->toDateString(),
            'timezone' => $tenant->timezone(),
            'slots' => array_map(static fn ($slot): array => $slot->toArray(), $slots),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();

        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'staff_id' => ['nullable', 'integer'],
            'starts_at' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:190'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $phone = Phone::normalize($data['phone']);

        if (! Phone::isIranianMobile($phone)) {
            throw ValidationException::withMessages([
                'phone' => 'شمارهٔ همراه باید ۱۱ رقم و با ۰۹ شروع شود.',
            ]);
        }

        $service = Service::query()->where('is_active', true)->findOrFail($data['service_id']);
        $staff = isset($data['staff_id']) ? Staff::query()->active()->findOrFail($data['staff_id']) : null;

        $startsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $data['starts_at'], $tenant->timezone());

        if ($startsAt === false) {
            throw ValidationException::withMessages(['starts_at' => 'زمان انتخاب‌شده معتبر نیست.']);
        }

        $booking = $this->bookings->book(
            $tenant,
            $service,
            $staff,
            $startsAt,
            [
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
            ],
            $data['notes'] ?? null,
        );

        return redirect()
            ->route('public.confirmation', ['tenant' => $tenant->slug, 'code' => $booking->code])
            ->with('status', 'نوبت شما با موفقیت ثبت شد.');
    }

    public function confirmation(Request $request): View
    {
        $tenant = $this->context->requireTenant();

        $booking = Booking::query()
            ->with(['service', 'staff', 'customer'])
            ->where('code', (string) $request->route('code'))
            ->firstOrFail();

        return view('public.confirmation', [
            'tenant' => $tenant,
            'booking' => $booking,
            'startsAt' => $booking->startsAtIn($tenant->timezone()),
            'canCancel' => $this->canCustomerCancel($tenant, $booking),
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $tenant = $this->context->requireTenant();

        $booking = Booking::query()->where('code', (string) $request->route('code'))->firstOrFail();

        $this->bookings->cancel($booking, 'لغو توسط مشتری', true);

        return redirect()
            ->route('public.confirmation', ['tenant' => $tenant->slug, 'code' => $booking->code])
            ->with('status', 'نوبت شما لغو شد.');
    }

    private function canCustomerCancel(Tenant $tenant, Booking $booking): bool
    {
        if (! $booking->status->isBlocking()) {
            return false;
        }

        $deadline = $booking->startsAtIn($tenant->timezone())
            ->subHours($tenant->cancellationWindowHours());

        return CarbonImmutable::now($tenant->timezone())->lessThan($deadline);
    }
}
