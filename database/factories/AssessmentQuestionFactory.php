<?php

namespace Database\Factories;

use App\Enums\AssessmentQuestionType;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentQuestion>
 */
class AssessmentQuestionFactory extends Factory
{
    protected $model = AssessmentQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_id' => Assessment::factory(),
            'description' => fake()->paragraph(),
            'points' => fake()->numberBetween(10, 50),
            'question_type' => AssessmentQuestionType::Essay,
            'order' => 1,
        ];
    }
}
