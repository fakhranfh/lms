<?php

namespace Database\Factories;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentQuestionAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentQuestionAnswer>
 */
class AssessmentQuestionAnswerFactory extends Factory
{
    protected $model = AssessmentQuestionAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_attempt_id' => AssessmentAttempt::factory(),
            'assessment_question_id' => AssessmentQuestion::factory(),
            'selected_option_id' => null,
            'answer_text' => null,
            'score' => null,
        ];
    }
}
