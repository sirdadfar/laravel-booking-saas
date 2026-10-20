<?php

namespace App\Concerns;

use App\Exceptions\TenantNotResolvedException;
use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Adds tenant scoping to a model:
 *  - a global scope that filters by the current TenantContext,
 *  - automatic tenant_id on create,
 *  - an immutable tenant_id on update.
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                throw new TenantNotResolvedException;
            }

            $model->setAttribute('tenant_id', $tenantId);
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('tenant_id')) {
                throw new LogicException('tenant_id is immutable.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
