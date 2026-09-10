<?php

namespace App\Services;

use App\Enums\AssessmentQuestionType;
use App\Models\AssessmentQuestion;

class AssessmentQuestionAttemptScoringService
{
    public function scoreObjectiveAnswer(AssessmentQuestion $question, ?string $selectedOptionId): ?float
    {
        if ($question->question_type !== AssessmentQuestionType::MultipleChoice) {
            return null;
        }

        if ($selectedOptionId === null) {
            return 0.0;
        }

        $correctOption = $question->options->firstWhere('is_correct', true);

        return $correctOption && $correctOption->id === $selectedOptionId ? (float) $question->points : 0.0;
    }
}
