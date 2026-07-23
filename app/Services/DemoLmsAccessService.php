<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\DemoLmsAccess;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;

class DemoLmsAccessService
{
    /**
     * Generate a unique access token for demo LMS.
     */
    public function generateAccessToken(School $school): string
    {
        $token = Str::random(32);

        while (DemoLmsAccess::where('access_token', $token)->exists()) {
            $token = Str::random(32);
        }

        return $token;
    }

    /**
     * Create a demo user account for the school.
     */
    public function createDemoUser(School $school, string $roleType = 'instructor'): User
    {
        $suffix = $roleType === 'student' ? '-student' : '';
        $email = 'demo'.$suffix.'-'.$school->id.'@demo.'.$school->domain;
        $name = $roleType === 'student' ? 'Demo Student' : 'Demo Instructor';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name.' - '.$school->name,
                'school_id' => $school->id,
                'password' => bcrypt('demo-password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->roles()->exists()) {
            $roleName = $roleType === 'student' ? RoleName::Student : RoleName::Instructor;

            $role = Role::where('school_id', $school->id)
                ->where('name', $roleName->value)
                ->firstOrCreate(
                    ['school_id' => $school->id, 'name' => $roleName->value],
                    ['guard_name' => 'web', 'slug' => $roleName->slug()]
                );

            // Sync permissions based on role type
            if (! $role->permissions()->exists()) {
                if ($roleType === 'student') {
                    $permissions = Permission::where('name', 'like', 'courses.%')
                        ->where('name', 'like', '%view')
                        ->get();
                } else {
                    $permissions = Permission::where('name', '!=', 'settings.billing')->get();
                }
                $role->syncPermissions($permissions);
            }

            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * Grant demo access to a user for 14 days.
     */
    public function grantDemoAccess(School $school, User $user, string $roleType = 'instructor'): DemoLmsAccess
    {
        $token = $this->generateAccessToken($school);
        $expiresAt = now()->addDays(14);

        return DemoLmsAccess::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'access_token' => $token,
            'role' => $roleType,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Check if demo access is still valid.
     */
    public function isDemoAccessValid(DemoLmsAccess $access): bool
    {
        if ($access->expires_at === null) {
            return false;
        }

        return $access->expires_at->isFuture();
    }

    /**
     * Get or create demo access for a school.
     */
    public function getOrCreateDemoAccess(School $school, string $roleType = 'instructor'): DemoLmsAccess
    {
        $validAccess = DemoLmsAccess::where('school_id', $school->id)
            ->where('role', $roleType)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        if ($validAccess) {
            return $validAccess;
        }

        $user = $this->createDemoUser($school, $roleType);

        return $this->grantDemoAccess($school, $user, $roleType);
    }

    /**
     * Regenerate demo access for a school, always creating a new token.
     */
    public function regenerateDemoAccess(School $school, string $roleType = 'instructor'): DemoLmsAccess
    {
        $user = $this->createDemoUser($school, $roleType);

        return $this->grantDemoAccess($school, $user, $roleType);
    }

    /**
     * Get both instructor and student demo credentials.
     */
    public function getDemoCredentials(School $school): array
    {
        return [
            'instructor' => $this->getOrCreateDemoAccess($school, 'instructor'),
            'student' => $this->getOrCreateDemoAccess($school, 'student'),
        ];
    }

    /**
     * Build the demo login URL for a school.
     */
    public function buildDemoLoginUrl(School $school, string $token, string $scheme = 'http', ?int $port = null): string
    {
        $url = "{$scheme}://{$school->domain}/demo-lms/login/{$token}";

        if ($port && ! in_array($port, [80, 443])) {
            $url = "{$scheme}://{$school->domain}:{$port}/demo-lms/login/{$token}";
        }

        return $url;
    }
}
