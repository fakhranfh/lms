<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    /**
     * Re-entrancy guard: resolving the authenticated user (auth()->user())
     * itself runs a query against the `users` table, which this same scope
     * would apply to. Without this guard that inner query would call
     * auth()->user() again before the guard has cached the resolved user,
     * recursing until memory is exhausted.
     */
    private static bool $resolvingAuthUser = false;

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$resolvingAuthUser) {
            return;
        }

        self::$resolvingAuthUser = true;

        try {
            $schoolId = auth()->user()?->school_id;
        } finally {
            self::$resolvingAuthUser = false;
        }

        if (! $schoolId) {
            return;
        }

        // Users belong to schools via the school_user pivot, and School Admins
        // may additionally (or instead) manage several schools via the
        // school_admins pivot, so they must remain visible under any school
        // they are either a member of or an admin for.
        if ($model instanceof User) {
            $builder->where(function (Builder $query) use ($schoolId): void {
                $query->whereExists(function ($subQuery) use ($schoolId): void {
                    $subQuery->selectRaw('1')
                        ->from('school_user')
                        ->whereColumn('school_user.user_id', 'users.id')
                        ->where('school_user.school_id', $schoolId);
                })->orWhereExists(function ($subQuery) use ($schoolId): void {
                    $subQuery->selectRaw('1')
                        ->from('school_admins')
                        ->whereColumn('school_admins.user_id', 'users.id')
                        ->where('school_admins.school_id', $schoolId);
                });
            });

            return;
        }

        $builder->where($model->qualifyColumn('school_id'), $schoolId);
    }
}
