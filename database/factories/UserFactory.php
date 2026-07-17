<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

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

        // Create school directly to avoid factory trait closure issues
        $school = School::create([
            'id' => Str::uuid(),
            'name' => fake()->company(),
            'domain' => fake()->unique()->domainName(),
            'tier_id' => $basicTier->id,
        ]);

        return [
            'school_id' => $school->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password@123123'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
