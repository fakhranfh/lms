<?php

namespace App\Jobs;

use App\Enums\ProctorSessionStatus;
use App\Enums\QuizQuestionType;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuizAnswerService;
use App\Services\AssessmentService;
use App\Services\GradebookScoringService;
use App\Services\ProctorSessionService;
use App\Services\QuizAttemptScoringService;
use App\Services\QuizService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;

class FinalizeExamSubmissionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $attemptId,
    ) {}

    public function handle(
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentQuizAnswerService $assessmentQuizAnswerService,
        QuizAttemptScoringService $quizAttemptScoringService,
        ProctorSessionService $proctorSessionService,
        QuizService $quizService,
        AssessmentService $assessmentService,
        GradebookScoringService $gradebookScoringService,
    ): void {
        $attempt = $assessmentAttemptService->find($this->attemptId);

        if ($attempt === null) {
            return;
        }

        $quiz = $quizService->findByAssessment($attempt->assessment_id, ['questions.options']);

        if ($quiz === null) {
            return;
        }

        $answers = Redis::hgetall("proctor_exam_answers:{$this->attemptId}") ?: [];

        foreach ($quiz->questions as $question) {
            $value = $answers[$question->id] ?? null;

            $isObjective = in_array($question->question_type, [QuizQuestionType::MultipleChoice, QuizQuestionType::TrueFalse], true);

            $assessmentQuizAnswerService->create([
                'assessment_attempt_id' => $this->attemptId,
                'quiz_question_id' => $question->id,
                'selected_option_id' => $isObjective ? ($value ?: null) : null,
                'answer_text' => $isObjective ? null : ($value ?: null),
                'score' => $isObjective ? $quizAttemptScoringService->scoreObjectiveAnswer($question, $value ?: null) : null,
            ]);
        }

        $deadline = $quiz->time_limit_per_attempt
            ? $attempt->started_at->copy()->addMinutes($quiz->time_limit_per_attempt)
            : null;

        $assessmentAttemptService->update($this->attemptId, [
            'submitted_at' => $deadline && now()->greaterThan($deadline) ? $deadline : now(),
        ]);

        $quizAttemptScoringService->recomputeForUser($quiz, $attempt->assessment_id, $attempt->user_id);

        $assessment = $assessmentService->find($attempt->assessment_id, ['course']);
        if ($assessment !== null) {
            $gradebookScoringService->recomputeForUser($assessment->course, $attempt->user_id);
        }

        $session = $proctorSessionService->findByAttempt($this->attemptId);
        if ($session !== null) {
            $proctorSessionService->update($session->id, [
                'status' => ProctorSessionStatus::Completed,
                'ended_at' => now(),
            ]);
            $proctorSessionService->clearSubmitting($session->id);
        }

        Redis::del("proctor_exam_answers:{$this->attemptId}");
    }
}
