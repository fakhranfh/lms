<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SchoolScope;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Model;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->getAttribute('school_id'))) {
                $model->setAttribute('school_id', app(CurrentSchool::class)->getSchoolId());
            }
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);
    }
}
