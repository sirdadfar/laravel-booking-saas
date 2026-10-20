<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Clear the tenant context at the start of every HTTP request.
 *
 * ResolveTenant already does this on tenant routes. This global guard covers
 * routes without a tenant (login, platform) and makes the "no tenant means no
 * rows" contract independent of the worker model: under Octane a singleton
 * could otherwise retain the previous request's tenant until the next tenant
 * route resets it.
 */
class ResetTenantContext
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->forget();

        return $next($request);
    }
}
