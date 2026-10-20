<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Scopes\TenantScope;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        return view('platform.tenants', [
            'tenants' => Tenant::query()
                /**
                 * The counted models carry the fail-closed TenantScope. There is
                 * no tenant context on a platform route, so without this opt-out
                 * every count silently reads as zero. The relation itself already
                 * constrains rows to `tenants.id`, so dropping the scope here can
                 * never mix tenants together.
                 */
                ->withCount([
                    'services' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
                    'staff' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
                    'bookings' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
                    'users' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
                ])
                ->orderBy('name')
                ->paginate(100),
        ]);
    }
}
