<?php

namespace App\Repositories\School;

use App\Models\PricingTier;
use App\Models\School;
use Illuminate\Support\Facades\Cache;

class SchoolRepository implements SchoolRepositoryInterface
{
    public function create(array $data): School
    {
        $tierId = $data['tier_id'] ?? $this->getDefaultTierId();

        return School::create([
            'name' => $data['name'],
            'domain' => $data['domain'],
            'tier_id' => $tierId,
        ]);
    }

    /**
     * Get the default tier ID (Basic tier).
     */
    private function getDefaultTierId(): int
    {
        return Cache::remember(
            'default_pricing_tier_id',
            3600,
            fn () => PricingTier::where('slug', 'basic')->value('id') ?? 1
        );
    }
}
