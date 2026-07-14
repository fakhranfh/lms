<?php

namespace Database\Factories;

use App\Enums\TierChangeType;
use App\Models\PricingTier;
use App\Models\SchoolTier;
use App\Models\TierChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TierChange>
 */
class TierChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_tier_id' => SchoolTier::factory(),
            'from_tier_id' => null,
            'to_tier_id' => PricingTier::factory(),
            'change_type' => TierChangeType::Initial,
            'reason' => null,
            'changed_at' => $this->faker->dateTime(),
        ];
    }
}
