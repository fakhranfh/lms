<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->getAttribute('school_id'))) {
                $model->setAttribute('school_id', auth()->user()?->school_id);
            }
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);
    }
}
