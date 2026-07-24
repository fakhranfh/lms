<?php

namespace App\Repositories\DemoLmsAccess;

interface DemoLmsAccessRepositoryInterface
{
    /**
     * Determine whether the given user has a demo LMS access record.
     */
    public function existsForUser(string $userId): bool;
}
