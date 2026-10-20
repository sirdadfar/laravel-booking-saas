<?php

namespace App\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Applies `where tenant_id = ?` to every tenant-owned model.
 *
 * When no tenant context is set the scope fails closed (returns no rows)
 * instead of leaking every tenant's data. Platform-wide queries must opt out
 * explicitly with withoutGlobalScope(TenantScope::class) and they only exist
 * for the Tenant model itself.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
