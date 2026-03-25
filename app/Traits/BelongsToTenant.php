<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && !auth()->user()->is_admin) {
                // Determine user's tenant (owner_id fallback to their own id)
                $tenantId = auth()->user()->tenant_id ?: auth()->id();
                $builder->where($builder->getQuery()->from . '.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && !auth()->user()->is_admin && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id ?: auth()->id();
            }
        });
    }
}
