<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = app(CurrentTenant::class)->getTenantId();
            }
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
