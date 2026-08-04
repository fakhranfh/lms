<?php

namespace Database\Factories;

use App\Enums\QuizScoringMethod;
use App\Models\Assessment;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_id' => Assessment::factory(),
            'start_date' => fake()->dateTimeBetween('now', '+1 week'),
            'due_date' => fake()->dateTimeBetween('+2 weeks', '+1 month'),
            'total_question' => 10,
            'total_attempts' => 2,
            'scoring_method' => QuizScoringMethod::Highest,
            'time_limit_per_attempt' => 30,
        ];
    }
}
