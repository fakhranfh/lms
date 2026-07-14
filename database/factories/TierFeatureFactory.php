<?php

namespace Database\Factories;

use App\Models\PricingTier;
use App\Models\TierFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TierFeature>
 */
class TierFeatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pricing_tier_id' => PricingTier::factory(),
            'feature_key' => $this->faker->unique()->word(),
            'is_enabled' => true,
        ];
    }
}
