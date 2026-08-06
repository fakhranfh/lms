<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\SessionProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SessionProgress>
 */
class SessionProgressFactory extends Factory
{
    protected $model = SessionProgress::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'session_id' => Session::factory(),
            'user_id' => User::factory(),
            'percent' => $this->faker->numberBetween(0, 100),
        ];
    }
}
