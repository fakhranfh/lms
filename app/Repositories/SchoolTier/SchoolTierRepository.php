<?php

namespace App\Repositories\SchoolTier;

use App\Enums\SubscriptionStatus;
use App\Models\SchoolTier;

class SchoolTierRepository implements SchoolTierRepositoryInterface
{
    public function create(array $data): SchoolTier
    {
        return SchoolTier::create($data);
    }

    public function update(string $id, array $data): SchoolTier
    {
        $schoolTier = SchoolTier::findOrFail($id);
        $schoolTier->update($data);

        return $schoolTier;
    }

    public function hasPendingForSchool(string $schoolId): bool
    {
        return SchoolTier::where('school_id', $schoolId)
            ->where('status', SubscriptionStatus::Pending)
            ->exists();
    }

    public function findPendingForSchool(string $schoolId): ?SchoolTier
    {
        return SchoolTier::where('school_id', $schoolId)
            ->where('status', SubscriptionStatus::Pending)
            ->first();
    }
}
