<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from (in order):
 *
 *   1. the {tenant} route parameter   -> /demo/book
 *   2. the X-Tenant header             -> API clients and tests
 *   3. the subdomain                   -> demo.localhost/book
 *
 * If the route carries a {tenant} parameter that does not exist, the request
 * is a 404: there is no fallback to "no tenant", because that would silently
 * turn a typo into a different page instead of an error.
 */
class ResolveTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Start from a clean slate. In a long-running worker (Octane, queue
        // worker, test client) the singleton outlives a single request; without
        // this reset, "no tenant in this request" would silently reuse the
        // previous request's tenant.
        $this->context->forget();

        [$slug, $source] = $this->candidate($request);

        if ($slug === null) {
            return $next($request);
        }

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($tenant === null) {
            abort(404, 'این کسب‌وکار پیدا نشد.');
        }

        $this->context->set($tenant, $source);

        return $next($request);
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private function candidate(Request $request): array
    {
        $routeTenant = $request->route('tenant');

        if (is_string($routeTenant) && $routeTenant !== '') {
            return [$routeTenant, 'path'];
        }

        $header = (string) config('tenancy.header', 'X-Tenant');
        $headerValue = $request->header($header);

        if (is_string($headerValue) && $headerValue !== '') {
            return [$headerValue, 'header'];
        }

        $subdomain = $this->subdomain($request->getHost());

        if ($subdomain !== null) {
            return [$subdomain, 'subdomain'];
        }

        return [null, 'none'];
    }

    /**
     * demo.localhost -> demo, when "localhost" is a configured base domain.
     * The bare base domain itself (localhost) is not a tenant.
     */
    private function subdomain(string $host): ?string
    {
        $host = strtolower($host);
        $bases = (array) config('tenancy.base_domains', []);

        if ($bases === []) {
            return null;
        }

        // The longest matching base domain wins: "shop.example.test" before "example.test".
        usort($bases, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($bases as $base) {
            $base = strtolower(trim($base));

            if ($base === '' || ! str_ends_with($host, '.'.$base)) {
                continue;
            }

            $label = substr($host, 0, -strlen('.'.$base));

            if ($label !== '' && ! str_contains($label, '.')) {
                return $label;
            }
        }

        return null;
    }
}
