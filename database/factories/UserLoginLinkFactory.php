<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserLoginLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserLoginLink>
 */
class UserLoginLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => Str::random(48),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }

    public function used(): static
    {
        return $this->state(['used_at' => now()]);
    }
}
