<?php

namespace App\Repositories\DemoLmsAccess;

use App\Models\DemoLmsAccess;

class DemoLmsAccessRepository implements DemoLmsAccessRepositoryInterface
{
    public function existsForUser(string $userId): bool
    {
        return DemoLmsAccess::where('user_id', $userId)->exists();
    }
}
