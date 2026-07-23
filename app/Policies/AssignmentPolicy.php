<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy extends BasePolicy
{
    public function view(User $user, Assignment $assignment): bool
    {
        return $this->userHasPermission($user, 'assignments.view');
    }

    public function create(User $user): bool
    {
        return $this->userHasPermission($user, 'assignments.create');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $this->userHasPermission($user, 'assignments.edit');
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->userHasPermission($user, 'assignments.delete');
    }
}
