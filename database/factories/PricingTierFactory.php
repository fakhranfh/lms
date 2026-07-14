<?php

namespace Database\Factories;

use App\Models\PricingTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingTier>
 */
class PricingTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(100000, 500000),
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'is_active' => true,
        ];
    }
}
