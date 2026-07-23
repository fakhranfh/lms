<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

class RolePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->userHasRole($user, RoleName::Admin) && $this->userHasPermission($user, 'roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->userHasRole($user, RoleName::Admin) && $this->userHasPermission($user, 'roles.view');
    }

    public function create(User $user): bool
    {
        return $this->userHasRole($user, RoleName::Admin) && $this->userHasPermission($user, 'roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->userHasRole($user, RoleName::Admin)
            && $this->userHasPermission($user, 'roles.edit')
            && $user->school_id === $role->school_id;
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->userHasRole($user, RoleName::Admin)
            && $this->userHasPermission($user, 'roles.delete')
            && ! $role->protected
            && $user->school_id === $role->school_id;
    }
}
