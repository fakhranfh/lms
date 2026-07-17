<?php

namespace Database\Factories;

use App\Models\DemoLmsAccess;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DemoLmsAccess>
 */
class DemoLmsAccessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => User::factory(),
            'access_token' => Str::random(32),
            'role' => 'instructor',
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'accessed_at' => null,
        ];
    }

    public function instructor(): static
    {
        return $this->state(['role' => 'instructor']);
    }

    public function student(): static
    {
        return $this->state(['role' => 'student']);
    }
}
