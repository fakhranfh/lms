<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\DemoLmsAccess;
use App\Models\School;
use App\Models\User;
use App\Repositories\DemoLmsAccess\DemoLmsAccessRepositoryInterface;
use App\Repositories\Permission\PermissionRepositoryInterface;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoLmsAccessService
{
    public function __construct(
        protected DemoLmsAccessRepositoryInterface $demoLmsAccessRepository,
        protected UserRepositoryInterface $userRepository,
        protected RoleRepositoryInterface $roleRepository,
        protected PermissionRepositoryInterface $permissionRepository,
    ) {}

    /**
     * Generate a unique access token for demo LMS.
     */
    public function generateAccessToken(School $school): string
    {
        $token = Str::random(32);

        while ($this->demoLmsAccessRepository->tokenExists($token)) {
            $token = Str::random(32);
        }

        return $token;
    }

    /**
     * Create a demo user account for the school.
     */
    public function createDemoUser(School $school, string $roleType = 'teacher'): User
    {
        $roleName = match ($roleType) {
            'student' => RoleName::Student,
            'school-admin' => RoleName::SchoolAdmin,
            default => RoleName::Teacher,
        };

        $suffix = '-'.$roleType;
        $email = 'demo'.$suffix.'-'.$school->id.'@demo.'.$school->domain;
        $name = 'Demo '.$roleName->label();

        $user = $this->userRepository->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name.' - '.$school->name,
                'school_id' => $school->id,
                'password' => bcrypt('demo-password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $this->userRepository->hasAnyRole($user)) {
            DB::transaction(function () use ($school, $roleName, $roleType, $user): void {
                $role = $this->roleRepository->firstOrCreateForSchool(
                    $school->id, $roleName->value, ['guard_name' => 'web', 'slug' => $roleName->slug()]
                );

                // Sync permissions based on role type
                if (! $this->roleRepository->hasPermissions($role)) {
                    $permissions = match ($roleType) {
                        'student' => $this->permissionRepository->getViewPermissionsFor('courses'),
                        default => $this->permissionRepository->getAllExcept('settings.billing'),
                    };
                    $this->roleRepository->syncPermissions($role, $permissions->pluck('id')->all());
                }

                $this->userRepository->assignRole($user, $role);
            });
        }

        return $user;
    }

    /**
     * Grant demo access to a user for 14 days.
     */
    public function grantDemoAccess(School $school, User $user, string $roleType = 'teacher'): DemoLmsAccess
    {
        $token = $this->generateAccessToken($school);
        $expiresAt = now()->addDays(14);

        return $this->demoLmsAccessRepository->create([
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
        return $access->expires_at->isFuture();
    }

    /**
     * Get or create demo access for a school.
     */
    public function getOrCreateDemoAccess(School $school, string $roleType = 'teacher'): DemoLmsAccess
    {
        $validAccess = $this->demoLmsAccessRepository->findValidForSchoolAndRole($school->id, $roleType);

        if ($validAccess) {
            return $validAccess;
        }

        $user = $this->createDemoUser($school, $roleType);

        return $this->grantDemoAccess($school, $user, $roleType);
    }

    /**
     * Regenerate demo access for a school, always creating a new token.
     */
    public function regenerateDemoAccess(School $school, string $roleType = 'teacher'): DemoLmsAccess
    {
        $user = $this->createDemoUser($school, $roleType);

        return $this->grantDemoAccess($school, $user, $roleType);
    }

    /**
     * Get both teacher and student demo credentials.
     */
    public function getDemoCredentials(School $school): array
    {
        return [
            'teacher' => $this->getOrCreateDemoAccess($school, 'teacher'),
            'student' => $this->getOrCreateDemoAccess($school, 'student'),
        ];
    }

    /**
     * Build the demo login URL for a school.
     */
    public function buildDemoLoginUrl(School $school, string $token, string $scheme = 'http', ?int $port = null): string
    {
        $domain = config('app.domain');

        $url = "{$scheme}://{$domain}/demo-lms/login/{$token}";

        if ($port && ! in_array($port, [80, 443])) {
            $url = "{$scheme}://{$domain}:{$port}/demo-lms/login/{$token}";
        }

        return $url;
    }
}
