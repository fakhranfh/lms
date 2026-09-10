<?php

namespace App\Jobs;

use App\Enums\AssessmentQuestionType;
use App\Enums\ProctorSessionStatus;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionAnswerService;
use App\Services\AssessmentQuestionAttemptScoringService;
use App\Services\AssessmentService;
use App\Services\GradebookScoringService;
use App\Services\ProctorExamAnswersService;
use App\Services\ProctorSessionService;
use App\Services\RichTextAttachmentCleanupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeExamSubmissionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $attemptId,
    ) {}

    public function handle(
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentQuestionAnswerService $assessmentQuestionAnswerService,
        AssessmentQuestionAttemptScoringService $assessmentQuestionAttemptScoringService,
        ProctorSessionService $proctorSessionService,
        AssessmentService $assessmentService,
        GradebookScoringService $gradebookScoringService,
        ProctorExamAnswersService $proctorExamAnswersService,
        RichTextAttachmentCleanupService $richTextAttachmentCleanupService,
    ): void {
        $attempt = $assessmentAttemptService->find($this->attemptId);

        if ($attempt === null) {
            return;
        }

        $assessment = $assessmentService->find($attempt->assessment_id, ['course', 'questions.options']);

        if ($assessment === null || $assessment->questions->isEmpty()) {
            return;
        }

        $answers = $proctorExamAnswersService->all($this->attemptId);

        foreach ($assessment->questions as $question) {
            $value = $answers[$question->id] ?? null;

            $isObjective = $question->question_type === AssessmentQuestionType::MultipleChoice;

            $assessmentQuestionAnswerService->create([
                'assessment_attempt_id' => $this->attemptId,
                'assessment_question_id' => $question->id,
                'selected_option_id' => $isObjective ? ($value ?: null) : null,
                'answer_text' => $isObjective ? null : ($value ? $richTextAttachmentCleanupService->promoteTempAttachments($value) : null),
                'score' => $isObjective ? $assessmentQuestionAttemptScoringService->scoreObjectiveAnswer($question, $value ?: null) : null,
            ]);
        }

        $assessmentAttemptService->update($this->attemptId, [
            'submitted_at' => now(),
        ]);

        $assessmentQuestionAttemptScoringService->recomputeForUser($attempt->assessment_id, $attempt->user_id);

        if ($assessment->course !== null) {
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

        $proctorExamAnswersService->clear($this->attemptId);
    }
}
