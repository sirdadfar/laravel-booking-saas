<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        return view('platform.tenants', [
            'tenants' => Tenant::query()
                ->withCount(['services', 'staff', 'bookings', 'users'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
