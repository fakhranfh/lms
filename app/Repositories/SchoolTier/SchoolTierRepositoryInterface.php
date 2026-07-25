<?php

namespace App\Repositories\SchoolTier;

use App\Models\SchoolTier;

interface SchoolTierRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SchoolTier;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): SchoolTier;

    /**
     * Determine whether the school has a pending tier change.
     */
    public function hasPendingForSchool(string $schoolId): bool;

    /**
     * Find the pending tier change for a school, if any.
     */
    public function findPendingForSchool(string $schoolId): ?SchoolTier;
}
