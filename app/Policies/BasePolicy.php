<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

abstract class BasePolicy
{
    protected function userHasPermission(User $user, string $permissionSlug): bool
    {
        return $user->hasPermissionTo($permissionSlug);
    }

    protected function userHasRole(User $user, RoleName|string $role): bool
    {
        return $user->hasRole($role);
    }
}
