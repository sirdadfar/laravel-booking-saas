<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly TenantContext $context) {}

    public function __invoke(Request $request): View
    {
        $tenant = $this->context->requireTenant();
        $tz = $tenant->timezone();
        $now = CarbonImmutable::now($tz);

        $todayBookings = Booking::query()
            ->with(['service', 'staff', 'customer'])
            ->whereBetween('starts_at', [
                $now->startOfDay()->utc(),
                $now->endOfDay()->utc(),
            ])
            ->orderBy('starts_at')
            ->get();

        $upcoming = Booking::query()
            ->with(['service', 'staff', 'customer'])
            ->where('starts_at', '>=', $now->utc())
            ->blocking()
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $weekStart = $now->startOfWeek(CarbonImmutable::SATURDAY)->utc();

        $weekCount = Booking::query()
            ->where('starts_at', '>=', $weekStart)
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])
            ->count();

        $monthRevenue = (int) Booking::query()
            ->where('starts_at', '>=', $now->startOfMonth()->utc())
            ->where('status', BookingStatus::Completed->value)
            ->sum('price');

        $statusCounts = Booking::query()
            ->selectRaw('status, COUNT(*) as total')
            ->where('starts_at', '>=', $now->startOfMonth()->utc())
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'tenant' => $tenant,
            'todayBookings' => $todayBookings,
            'upcoming' => $upcoming,
            'weekCount' => $weekCount,
            'monthRevenue' => $monthRevenue,
            'statusCounts' => $statusCounts,
            'now' => $now,
        ]);
    }
}
