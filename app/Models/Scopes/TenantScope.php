<?php

namespace App\Models\Scopes;

use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = app(CurrentTenant::class)->getTenantId()) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
        }
    }
}
