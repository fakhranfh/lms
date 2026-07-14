<?php

namespace App\Models\Scopes;

use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($schoolId = app(CurrentSchool::class)->getSchoolId()) {
            $builder->where($model->qualifyColumn('school_id'), $schoolId);
        }
    }
}
