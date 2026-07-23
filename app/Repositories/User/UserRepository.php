<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class UserRepository implements UserRepositoryInterface
{
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function setPendingEmail(User $user, string $pendingEmail): void
    {
        $user->update(['pending_email' => $pendingEmail]);
    }

    public function confirmPendingEmail(User $user): void
    {
        $user->update([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ]);
    }

    public function getAll(array $with = []): Collection
    {
        return User::with($with)->get();
    }

    public function find(string $id): ?User
    {
        return User::find($id);
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        $roles = Role::whereIn('id', $roleIds)->get();
        $user->syncRoles($roles);
    }
}
