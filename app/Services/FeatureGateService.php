<?php

namespace App\Services;

use App\Enums\TierFeature;
use App\Enums\TierLimit;
use App\Exceptions\FeatureNotAvailableException;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FeatureGateService
{
    /**
     * Check if a user or school has access to a feature.
     * Features are no longer supported - all tiers have all features.
     */
    public function can(User|School|Model $entity, TierFeature $feature): bool
    {
        $school = $this->getSchool($entity);

        return $school !== null;
    }

    /**
     * Get a limit value for a user or school.
     * Returns null if the limit is unlimited.
     */
    public function limit(User|School|Model $entity, TierLimit $limit): ?int
    {
        $school = $this->getSchool($entity);

        if (! $school) {
            return null;
        }

        return $school->getCurrentTierLimit($limit->value);
    }

    /**
     * Throw an exception if the feature is not available.
     *
     *
     * @throws FeatureNotAvailableException
     */
    public function requireFeature(User|School|Model $entity, TierFeature $feature): void
    {
        if (! $this->can($entity, $feature)) {
            throw new FeatureNotAvailableException(
                "Feature '{$feature->label()}' is not available in your subscription tier."
            );
        }
    }

    /**
     * Check if a limit has been exceeded.
     */
    public function isLimitExceeded(User|School|Model $entity, TierLimit $limit, int $current): bool
    {
        $maxLimit = $this->limit($entity, $limit);

        if ($maxLimit === null) {
            return false;
        }

        return $current > $maxLimit;
    }

    /**
     * Get the school from a user or school entity.
     */
    private function getSchool(User|School|Model $entity): ?School
    {
        if ($entity instanceof School) {
            return $entity;
        }

        if ($entity instanceof User) {
            return $entity->school();
        }

        return null;
    }
}
