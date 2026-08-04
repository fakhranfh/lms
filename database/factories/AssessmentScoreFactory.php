<?php

namespace Database\Factories;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentScore>
 */
class AssessmentScoreFactory extends Factory
{
    protected $model = AssessmentScore::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_attempt_id' => AssessmentAttempt::factory(),
            'score' => fake()->numberBetween(60, 100),
            'graded_by' => User::factory(),
            'graded_at' => fake()->dateTimeBetween('now', '+1 week'),
            'feedback' => fake()->sentence(),
        ];
    }
}
