<?php

namespace App\Services;

use App\Exceptions\TenantNotResolvedException;
use App\Models\Tenant;
use Closure;

/**
 * Holds the tenant for the current HTTP request (or the current CLI/test
 * operation). Registered as a singleton in AppServiceProvider.
 *
 * The context is the single source of truth for TenantScope and for the
 * BelongsToTenant trait. It is deliberately not stored in the session: the
 * tenant is resolved from the request on every request.
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    private ?string $source = null;

    public function set(Tenant $tenant, string $source = 'manual'): void
    {
        $this->tenant = $tenant;
        $this->source = $source;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function requireTenant(): Tenant
    {
        return $this->tenant ?? throw new TenantNotResolvedException;
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * How the current tenant was resolved: subdomain, path, header or manual.
     */
    public function source(): ?string
    {
        return $this->source;
    }

    public function forget(): void
    {
        $this->tenant = null;
        $this->source = null;
    }

    /**
     * Run a callback with a temporary tenant and reliably restore the previous
     * context afterwards. Used by seeders, tests and console commands.
     */
    public function runFor(Tenant $tenant, Closure $callback): mixed
    {
        $previousTenant = $this->tenant;
        $previousSource = $this->source;

        $this->set($tenant, 'manual');

        try {
            return $callback();
        } finally {
            $this->tenant = $previousTenant;
            $this->source = $previousSource;
        }
    }
}
