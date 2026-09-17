<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Models\Concerns\HasViewerTimezoneDates;
use App\Models\Scopes\SchoolScope;
use App\Support\CurrentSchool;
use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'pending_email', 'password', 'profile_photo_path', 'timezone', 'school_id', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, HasRoles, HasUuid, HasViewerTimezoneDates, Notifiable, SoftDeletes;

    /**
     * The school id assigned via the `school_id` write-through, applied to the
     * school_user pivot once the user has been persisted.
     */
    private ?string $pendingSchoolId = null;

    private bool $pendingSchoolIdWasSet = false;

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::created(function (User $user): void {
            $schoolId = $user->pendingSchoolIdWasSet
                ? $user->pendingSchoolId
                : app(CurrentSchool::class)->getSchoolId();

            if ($schoolId !== null) {
                $user->memberSchools()->syncWithoutDetaching([$schoolId]);
            }
        });
    }

    /**
     * Whether `school_id` was explicitly assigned before this user was created.
     */
    public function schoolIdWasExplicitlySet(): bool
    {
        return $this->pendingSchoolIdWasSet;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function hasVerifiedEmail(): bool
    {
        if (! config('features.email_enabled')) {
            return true;
        }

        if ($this->hasRole(RoleName::Admin)) {
            return true;
        }

        return parent::hasVerifiedEmail();
    }

    /**
     * Get the schools this user is a member of.
     *
     * @return BelongsToMany<School, $this>
     */
    public function memberSchools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user')->withTimestamps();
    }

    /**
     * Get the school this user primarily belongs to.
     */
    public function school(): ?School
    {
        return $this->memberSchools()->first();
    }

    /**
     * Get/set the primary school id for the user, backed by the school_user pivot.
     * Setting this attribute before the user is persisted defers attachment
     * until after creation, since the pivot requires a user id.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function schoolId(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->school()?->id,
            set: function (?string $value): array {
                $this->pendingSchoolId = $value;
                $this->pendingSchoolIdWasSet = true;

                return [];
            },
        );
    }

    /**
     * Get the schools this user administers as a School Admin.
     *
     * @return BelongsToMany<School, $this>
     */
    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_admins')->withTimestamps();
    }
}
