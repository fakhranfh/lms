<?php

namespace App\Services;

use App\Enums\AssessmentQuestionType;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;

class AssessmentQuestionAttemptScoringService
{
    public function __construct(
        private AssessmentQuestionAnswerService $assessmentQuestionAnswerService,
        private AssessmentAttemptService $assessmentAttemptService,
        private AssessmentScoreService $assessmentScoreService,
    ) {}

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

    /**
     * Sum of an attempt's answer scores, treating ungraded (null) essay
     * answers as 0 for live/partial display purposes.
     */
    public function attemptTotal(string $attemptId): float
    {
        return (float) $this->assessmentQuestionAnswerService->forAttempt($attemptId)->sum('score');
    }

    /**
     * True once every answer on the attempt has a score, i.e. all
     * multiple-choice questions are auto-graded and any essay questions
     * have been manually graded.
     */
    public function isFullyGraded(string $attemptId): bool
    {
        $answers = $this->assessmentQuestionAnswerService->forAttempt($attemptId);

        return $answers->isNotEmpty() && $answers->every(fn ($answer) => $answer->score !== null);
    }

    /**
     * Writes/updates the AssessmentScore for a user's latest submitted
     * attempt once it is fully graded. Final exams only support "latest
     * attempt counts" scoring. Returns the scored attempt, or null if the
     * user has no submitted attempts yet or grading is still pending.
     */
    public function recomputeForUser(string $assessmentId, string $userId): ?AssessmentAttempt
    {
        $attempt = $this->assessmentAttemptService->forAssessmentAndUser($assessmentId, $userId)
            ->filter(fn (AssessmentAttempt $a) => $a->submitted_at !== null)
            ->last();

        if ($attempt === null || ! $this->isFullyGraded($attempt->id)) {
            return null;
        }

        $total = $this->attemptTotal($attempt->id);

        $existingScore = $this->assessmentScoreService->findByAttempt($attempt->id);
        $data = ['assessment_attempt_id' => $attempt->id, 'score' => $total];

        if ($existingScore) {
            $this->assessmentScoreService->update($existingScore->id, $data);
        } else {
            $this->assessmentScoreService->create($data);
        }

        return $attempt;
    }
}
