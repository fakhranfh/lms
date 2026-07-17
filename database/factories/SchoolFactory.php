<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolTier;
use App\Models\TierChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get or create the basic tier
        $basicTier = PricingTier::where('slug', 'basic')->first();
        if (! $basicTier) {
            $basicTier = PricingTier::create([
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Free tier for getting started',
                'price' => 0,
                'currency' => 'IDR',
                'billing_period' => BillingPeriod::Monthly->value,
                'is_active' => true,
            ]);
        }

        return [
            'name' => fake()->company(),
            'domain' => fake()->unique()->domainName(),
            'tier_id' => $basicTier->id,
        ];
    }

    /**
     * Configure the factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (School $school) {
            // Create SchoolTier record
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

            // Create initial tier change record
            TierChange::create([
                'school_tier_id' => $schoolTier->id,
                'from_tier_id' => null,
                'to_tier_id' => $school->tier_id,
                'change_type' => 'initial',
                'changed_at' => now(),
            ]);
        });
    }

    /**
     * Create a school with a specific pricing tier.
     */
    public function withTier(PricingTier $tier): static
    {
        return $this->state(fn () => [
            'tier_id' => $tier->id,
        ]);
    }
}
