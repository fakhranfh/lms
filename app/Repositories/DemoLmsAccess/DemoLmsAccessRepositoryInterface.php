<?php

namespace App\Repositories\DemoLmsAccess;

use App\Models\DemoLmsAccess;

interface DemoLmsAccessRepositoryInterface
{
    /**
     * Determine whether the given user has a demo LMS access record.
     */
    public function existsForUser(string $userId): bool;

    /**
     * Determine whether the given access token is already in use.
     */
    public function tokenExists(string $token): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DemoLmsAccess;

    /**
     * Find the latest still-valid (unexpired) access for a school + role.
     */
    public function findValidForSchoolAndRole(string $schoolId, string $role): ?DemoLmsAccess;
}
