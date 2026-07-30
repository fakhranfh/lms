<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class UserRepository implements UserRepositoryInterface
{
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with($with);

        if (! empty($filters['search'])) {
            $query->where(function ($query) use ($filters): void {
                $query->whereRaw('name ILIKE ?', ["%{$filters['search']}%"])
                    ->orWhereRaw('email ILIKE ?', ["%{$filters['search']}%"]);
            });
        }

        if (! empty($filters['role_id'])) {
            $query->whereHas('roles', function ($query) use ($filters): void {
                $query->where('roles.id', $filters['role_id']);
            });
        }

        if (! empty($filters['sort']) && ! empty($filters['direction'])) {
            $query->orderBy($filters['sort'], $filters['direction']);
        }

        return $query->paginate($perPage);
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

    public function firstOrCreate(array $attributes, array $values = []): User
    {
        return User::firstOrCreate($attributes, $values);
    }

    public function hasAnyRole(User $user): bool
    {
        return $user->roles()->exists();
    }

    public function assignRole(User $user, \App\Models\Role $role): void
    {
        $user->assignRole($role);
    }
}
