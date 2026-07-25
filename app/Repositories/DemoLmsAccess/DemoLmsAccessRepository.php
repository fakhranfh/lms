<?php

namespace App\Repositories\DemoLmsAccess;

use App\Models\DemoLmsAccess;

class DemoLmsAccessRepository implements DemoLmsAccessRepositoryInterface
{
    public function existsForUser(string $userId): bool
    {
        return DemoLmsAccess::where('user_id', $userId)->exists();
    }

    public function tokenExists(string $token): bool
    {
        return DemoLmsAccess::where('access_token', $token)->exists();
    }

    public function create(array $data): DemoLmsAccess
    {
        return DemoLmsAccess::create($data);
    }

    public function findValidForSchoolAndRole(string $schoolId, string $role): ?DemoLmsAccess
    {
        return DemoLmsAccess::where('school_id', $schoolId)
            ->where('role', $role)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }
}
