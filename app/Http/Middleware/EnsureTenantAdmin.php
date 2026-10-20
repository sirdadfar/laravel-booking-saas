<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only the tenant's own staff may enter its admin area. Platform admins are
 * allowed in every tenant (support use case).
 */
class EnsureTenantAdmin
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $this->context->tenant();

        if ($tenant === null) {
            abort(404);
        }

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! $user->canAccessTenant($tenant)) {
            abort(403, 'شما به پنل این کسب‌وکار دسترسی ندارید.');
        }

        return $next($request);
    }
}
