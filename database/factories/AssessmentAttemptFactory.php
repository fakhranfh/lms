<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentAttempt>
 */
class AssessmentAttemptFactory extends Factory
{
    protected $model = AssessmentAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_id' => Assessment::factory(),
            'user_id' => User::factory(),
            'group_id' => null,
            'submitted_by' => null,
            'attempt_number' => 1,
            'started_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'submitted_at' => fake()->dateTimeBetween('now', '+1 day'),
        ];
    }
}
