<?php

namespace Database\Factories;

use App\Models\DemoLmsAccess;
use App\Models\Tenant;
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
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'access_token' => Str::random(32),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'accessed_at' => null,
        ];
    }
}
