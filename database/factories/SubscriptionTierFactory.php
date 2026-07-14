<?php

namespace Database\Factories;

use App\Models\SubscriptionTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionTier>
 */
class SubscriptionTierFactory extends Factory
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
            'features' => [],
            'max_users' => $this->faker->numberBetween(10, 1000),
            'storage_gb' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
