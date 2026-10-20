<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Public\PublicBookingController;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly PublicBookingController $booking,
    ) {}

    public function __invoke(Request $request): View
    {
        // A subdomain request lands on "/" and is already tenant-resolved.
        if ($this->context->check()) {
            return $this->booking->show($request);
        }

        return view('home', [
            'tenants' => Tenant::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
