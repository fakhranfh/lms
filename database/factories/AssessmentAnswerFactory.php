<?php

namespace Database\Factories;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentAnswer>
 */
class AssessmentAnswerFactory extends Factory
{
    protected $model = AssessmentAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_attempt_id' => AssessmentAttempt::factory(),
            'answer_text' => fake()->paragraph(),
            'answer_file_id' => null,
            'comment' => null,
            'score' => null,
        ];
    }
}
