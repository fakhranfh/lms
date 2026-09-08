<?php

namespace Database\Factories;

use App\Models\AssessmentQuestion;
use App\Models\AssessmentQuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentQuestionOption>
 */
class AssessmentQuestionOptionFactory extends Factory
{
    protected $model = AssessmentQuestionOption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_question_id' => AssessmentQuestion::factory(),
            'label' => fake()->word(),
            'is_correct' => false,
            'order' => 1,
        ];
    }
}
