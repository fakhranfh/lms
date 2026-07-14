<?php

namespace Database\Factories;

use App\Models\PricingTier;
use App\Models\TierLimit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TierLimit>
 */
class TierLimitFactory extends Factory
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
            'limit_key' => $this->faker->unique()->word(),
            'limit_value' => $this->faker->numberBetween(100, 10000),
        ];
    }
}
