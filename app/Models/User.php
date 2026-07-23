<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Models\Concerns\BelongsToSchool;
use App\Models\Concerns\HasViewerTimezoneDates;
use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'pending_email', 'password', 'profile_photo_path', 'timezone', 'school_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BelongsToSchool, CanResetPassword, HasFactory, HasRoles, HasUuid, HasViewerTimezoneDates, Notifiable;

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
     * Get the school the user belongs to.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
