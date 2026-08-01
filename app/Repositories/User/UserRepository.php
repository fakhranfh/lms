<?php

namespace App\Repositories\User;

use App\Models\Scopes\SchoolScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }

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
                $query->whereLike('name', "%{$filters['search']}%", caseSensitive: false)
                    ->orWhereLike('email', "%{$filters['search']}%", caseSensitive: false);
            });
        }

        if (! empty($filters['name'])) {
            $query->whereLike('name', "%{$filters['name']}%", caseSensitive: false);
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

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function findByEmailAnySchool(string $email, ?string $ignoreUserId = null): ?User
    {
        return User::withoutGlobalScope(SchoolScope::class)
            ->withTrashed()
            ->where('email', $email)
            ->when($ignoreUserId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->first();
    }

    /**
     * Whether the given email belongs to a user who is a member of the given
     * school, checked directly against the school_user pivot rather than
     * User::school_id (a virtual accessor derived from the first pivot row,
     * unreliable for this comparison).
     */
    public function emailBelongsToSchool(string $email, string $schoolId, ?string $ignoreUserId = null): bool
    {
        return User::withoutGlobalScope(SchoolScope::class)
            ->withTrashed()
            ->where('email', $email)
            ->when($ignoreUserId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->whereHas('memberSchools', fn ($query) => $query->where('schools.id', $schoolId))
            ->exists();
    }

    public function existsByName(string $name, ?string $ignoreUserId = null): bool
    {
        return User::withoutGlobalScope(SchoolScope::class)
            ->where('name', $name)
            ->when($ignoreUserId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->exists();
    }

    public function findTrashedInSchool(string $email, string $schoolId): ?User
    {
        return User::withoutGlobalScope(SchoolScope::class)
            ->onlyTrashed()
            ->where('email', $email)
            ->whereHas('memberSchools', fn ($query) => $query->where('schools.id', $schoolId))
            ->first();
    }

    public function restore(User $user, array $data): User
    {
        $user->fill($data);
        $user->email_verified_at = now();
        $user->restore();

        return $user;
    }
}
