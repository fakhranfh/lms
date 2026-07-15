<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\School;
use App\Models\SchoolTier;
use App\Models\TierChange;
use App\Repositories\School\SchoolRepositoryInterface;

class SchoolService
{
    public function __construct(private SchoolRepositoryInterface $schoolRepository) {}

    public function create(array $data): School
    {
        $school = $this->schoolRepository->create($data);

        // Assign default tier to the school
        $this->assignDefaultTier($school);

        return $school;
    }

    /**
     * Assign the default tier to a school and create audit trail.
     */
    private function assignDefaultTier(School $school): SchoolTier
    {
        $schoolTier = SchoolTier::create([
            'school_id' => $school->id,
            'tier_id' => $school->tier_id,
            'status' => SubscriptionStatus::Active,
            'started_at' => now(),
            'expires_at' => null,
            'renewal_date' => null,
            'auto_renew' => true,
            'payment_method' => null,
        ]);

        // Create initial tier change record for audit trail
        TierChange::create([
            'school_tier_id' => $schoolTier->id,
            'from_tier_id' => null,
            'to_tier_id' => $school->tier_id,
            'change_type' => 'initial',
            'changed_at' => now(),
        ]);

        return $schoolTier;
    }

    public function buildRegisterUrl(School $school, string $scheme, int $port): string
    {
        $portSuffix = in_array($port, [80, 443], true) ? '' : ":{$port}";

        return "{$scheme}://{$school->domain}{$portSuffix}/register";
    }
}
