<?php

namespace App\Models\Scopes;

use App\Models\User;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! $schoolId = app(CurrentSchool::class)->getSchoolId()) {
            return;
        }

        $column = $model->qualifyColumn('school_id');

        // School Admin users have no fixed school_id (they may manage several
        // schools via the school_admins pivot), so they must remain visible
        // under the specific school(s) they actually administer.
        if ($model instanceof User) {
            $builder->where(function (Builder $query) use ($column, $schoolId): void {
                $query->where($column, $schoolId)
                    ->orWhere(function (Builder $adminQuery) use ($column, $schoolId): void {
                        $adminQuery->whereNull($column)
                            ->whereExists(function ($subQuery) use ($schoolId): void {
                                $subQuery->selectRaw('1')
                                    ->from('school_admins')
                                    ->whereColumn('school_admins.user_id', 'users.id')
                                    ->where('school_admins.school_id', $schoolId);
                            });
                    });
            });

            return;
        }

        $builder->where($column, $schoolId);
    }
}
