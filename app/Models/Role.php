<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'school_id', 'slug', 'protected'];

    protected $casts = [
        'protected' => 'boolean',
    ];

    public function delete(): ?bool
    {
        if ($this->protected) {
            throw new \Exception("The {$this->name} role cannot be deleted.");
        }

        return parent::delete();
    }

    public function forceDelete(): ?bool
    {
        if ($this->protected) {
            throw new \Exception("The {$this->name} role cannot be deleted.");
        }

        return parent::forceDelete();
    }

    /**
     * Get the school this role belongs to.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the permissions assigned to this role.
     *
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_has_permissions',
            'role_id',
            'permission_id'
        );
    }

    /**
     * Scope to roles for a specific school.
     */
    public function scopeForSchool($query, ?string $schoolId)
    {
        if ($schoolId === null) {
            return $query->whereNull('school_id');
        }

        return $query->where('school_id', $schoolId);
    }

    /**
     * Check if this role is global (no school assigned).
     */
    public function isGlobal(): bool
    {
        return $this->school_id === null;
    }

    /**
     * Override Spatie's create to scope the duplicate-name check by school_id,
     * since teams are not enabled and Spatie's own check would otherwise treat
     * roles with the same name in different schools as duplicates.
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= config('auth.defaults.guard');

        $exists = static::where('name', $attributes['name'])
            ->where('guard_name', $attributes['guard_name'])
            ->when(
                $attributes['school_id'] ?? null,
                fn ($query, $schoolId) => $query->where('school_id', $schoolId),
                fn ($query) => $query->whereNull('school_id'),
            )
            ->exists();

        if ($exists) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * Override Spatie's findByName to support school-scoped roles.
     * Only find global roles (school_id = null) by name to allow same role names per school.
     */
    public static function findByName(\BackedEnum|string $name, ?string $guardName = null): \Spatie\Permission\Contracts\Role
    {
        $guardName = $guardName ?? config('auth.defaults.guard');
        $name = $name instanceof \BackedEnum ? $name->value : $name;

        $role = static::where('name', $name)
            ->where('guard_name', $guardName)
            ->whereNull('school_id')
            ->first();

        if (! $role) {
            throw RoleAlreadyExists::create($name, $guardName);
        }

        return $role;
    }

    /**
     * Override Spatie's findById to get role by id.
     */
    public static function findById(int|string $id, ?string $guardName = null): \Spatie\Permission\Contracts\Role
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $role = static::where('id', $id)
            ->where('guard_name', $guardName)
            ->first();

        if (! $role) {
            throw RoleAlreadyExists::create((string) $id, $guardName);
        }

        return $role;
    }
}
