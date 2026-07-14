<?php

namespace Database\Factories;

use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolTier>
 */
class SchoolTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTime();
        $expiresAt = (clone $startedAt)->modify('+1 month');

        return [
            'school_id' => School::factory(),
            'tier_id' => PricingTier::factory(),
            'status' => 'active',
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'renewal_date' => null,
            'auto_renew' => true,
            'payment_method' => null,
        ];
    }
}
