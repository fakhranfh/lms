<?php

namespace App\Models;

use App\Traits\HasUuid;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $slug
 */
#[Fillable(['name', 'domain', 'logo_path'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, HasUuid;

    protected $table = 'schools';

    /**
     * Boot the model: generate a permanent slug from the school's name on
     * creation, and prevent it from ever being changed afterwards.
     */
    protected static function booted(): void
    {
        static::creating(function (School $school): void {
            if ($school->slug || ! Schema::hasColumn($school->getTable(), 'slug')) {
                return;
            }

            $base = Str::slug($school->name);
            $slug = $base;
            $suffix = 2;

            while (static::where('slug', $slug)->exists()) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $school->slug = $slug;
        });

        static::updating(function (School $school): void {
            if ($school->isDirty('slug')) {
                $school->slug = $school->getOriginal('slug');
            }
        });
    }

    /**
     * Get the users belonging to the school.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_user')->withTimestamps();
    }

    /**
     * Get the roles for this school.
     *
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get the School Admin users who manage this school.
     *
     * @return BelongsToMany<User, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_admins')->withTimestamps();
    }
}
