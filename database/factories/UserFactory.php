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
        return [
            'id' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password@123123'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure the model factory to attach the user to a school after creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (! $user->schoolIdWasExplicitlySet() && ! $user->memberSchools()->exists()) {
                $name = fake()->company();

                $school = School::create([
                    'id' => (string) Str::uuid(),
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.Str::random(6),
                    'domain' => fake()->unique()->domainName(),
                    'tier_id' => PricingTier::where('slug', 'basic')->first()?->id
                        ?? PricingTier::create([
                            'name' => 'Basic',
                            'slug' => 'basic',
                            'description' => 'Free tier for getting started',
                            'price' => 0,
                            'currency' => 'IDR',
                            'billing_period' => BillingPeriod::Monthly->value,
                            'is_active' => true,
                        ])->id,
                ]);

                $user->memberSchools()->attach($school);
            }
        });
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

    /**
     * Attach the created user to the given school via the school_user pivot.
     */
    public function forSchool(School $school): static
    {
        return $this->state(fn (array $attributes) => [
            'school_id' => $school->id,
        ]);
    }
}
