<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\SessionSubtopic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SessionSubtopic>
 */
class SessionSubtopicFactory extends Factory
{
    protected $model = SessionSubtopic::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'session_id' => Session::factory(),
            'subtopic' => fake()->sentence(4),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
