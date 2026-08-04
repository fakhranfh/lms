<?php

namespace Database\Factories;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssessmentQuizAnswer>
 */
class AssessmentQuizAnswerFactory extends Factory
{
    protected $model = AssessmentQuizAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'assessment_attempt_id' => AssessmentAttempt::factory(),
            'quiz_question_id' => QuizQuestion::factory(),
            'selected_option_id' => null,
            'answer_text' => null,
            'score' => null,
        ];
    }
}
