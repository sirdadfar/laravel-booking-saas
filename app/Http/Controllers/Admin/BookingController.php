<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly BookingService $bookings,
    ) {}

    public function index(Request $request): View
    {
        $tenant = $this->context->requireTenant();
        $tz = $tenant->timezone();

        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
        ]);

        $date = isset($data['date'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $data['date'], $tz)
            : CarbonImmutable::now($tz);

        $query = Booking::query()
            ->with(['service', 'staff', 'customer'])
            ->whereBetween('starts_at', [$date->startOfDay()->utc(), $date->endOfDay()->utc()])
            ->orderBy('starts_at');

        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return view('admin.bookings', [
            'tenant' => $tenant,
            // A single day can still hold a lot of rows; cap the page and keep
            // the date/status filters in the pagination links.
            'bookings' => $query->paginate(100)->withQueryString(),
            'date' => $date->toDateString(),
            'status' => $data['status'] ?? null,
            'statuses' => BookingStatus::cases(),
        ]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'reason' => ['nullable', 'string', 'max:190'],
        ]);

        $model = Booking::query()->findOrFail((int) $request->route('booking'));

        $this->bookings->transition(
            $model,
            BookingStatus::from($data['status']),
            $data['reason'] ?? null,
        );

        return back()->with('status', 'وضعیت نوبت به‌روزرسانی شد.');
    }
}
