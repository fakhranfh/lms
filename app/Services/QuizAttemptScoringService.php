<?php

namespace App\Services;

use App\Enums\QuizQuestionType;
use App\Enums\QuizScoringMethod;
use App\Models\AssessmentAttempt;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Collection;

class QuizAttemptScoringService
{
    public function __construct(
        private AssessmentQuizAnswerService $assessmentQuizAnswerService,
        private AssessmentAttemptService $assessmentAttemptService,
        private AssessmentScoreService $assessmentScoreService,
    ) {}

    public function scoreObjectiveAnswer(QuizQuestion $question, ?string $selectedOptionId): ?float
    {
        if (! in_array($question->question_type, [QuizQuestionType::MultipleChoice, QuizQuestionType::TrueFalse], true)) {
            return null;
        }

        if ($selectedOptionId === null) {
            return 0.0;
        }

        $correctOption = $question->options->firstWhere('is_correct', true);

        return $correctOption && $correctOption->id === $selectedOptionId ? (float) $question->points : 0.0;
    }

    /**
     * Sum of an attempt's answer scores, treating ungraded (null) answers as 0
     * for live/partial display purposes.
     */
    public function attemptTotal(string $attemptId): float
    {
        return $this->assessmentQuizAnswerService->forAttempt($attemptId)->sum('score');
    }

    public function hasPendingGrading(string $attemptId): bool
    {
        return $this->assessmentQuizAnswerService->forAttempt($attemptId)->contains(fn ($answer) => $answer->score === null);
    }

    /**
     * Recomputes the counted attempt (per the quiz's scoring method) for a
     * user and writes/updates its AssessmentScore. Returns the counted
     * attempt, or null if the user has no submitted attempts yet.
     */
    public function recomputeForUser(Quiz $quiz, string $assessmentId, string $userId): ?AssessmentAttempt
    {
        $attempts = $this->assessmentAttemptService->forAssessmentAndUser($assessmentId, $userId)
            ->filter(fn (AssessmentAttempt $attempt) => $attempt->submitted_at !== null)
            ->values();

        if ($attempts->isEmpty()) {
            return null;
        }

        $countedAttempt = $this->countedAttempt($attempts, $quiz->scoring_method);
        $total = $this->countedTotal($attempts, $quiz->scoring_method);

        $existingScore = $this->assessmentScoreService->findByAttempt($countedAttempt->id);
        $data = ['assessment_attempt_id' => $countedAttempt->id, 'score' => $total];

        if ($existingScore) {
            $this->assessmentScoreService->update($existingScore->id, $data);
        } else {
            $this->assessmentScoreService->create($data);
        }

        return $countedAttempt;
    }

    /**
     * @param  Collection<int, AssessmentAttempt>  $attempts
     */
    private function countedAttempt(Collection $attempts, QuizScoringMethod $method): AssessmentAttempt
    {
        return match ($method) {
            QuizScoringMethod::Latest => $attempts->last(),
            QuizScoringMethod::Highest, QuizScoringMethod::Average => $attempts->sortByDesc(
                fn (AssessmentAttempt $attempt) => $this->attemptTotal($attempt->id)
            )->first(),
        };
    }

    /**
     * @param  Collection<int, AssessmentAttempt>  $attempts
     */
    private function countedTotal(Collection $attempts, QuizScoringMethod $method): float
    {
        $totals = $attempts->map(fn (AssessmentAttempt $attempt) => $this->attemptTotal($attempt->id));

        return match ($method) {
            QuizScoringMethod::Highest => $totals->max(),
            QuizScoringMethod::Latest => $totals->last(),
            QuizScoringMethod::Average => round($totals->average(), 2),
        };
    }
}
