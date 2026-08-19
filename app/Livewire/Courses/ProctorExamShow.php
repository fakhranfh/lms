<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorEventType;
use App\Enums\ProctorSessionStatus;
use App\Enums\ProctorSeverity;
use App\Enums\ProctorSnapshotType;
use App\Enums\QuizQuestionType;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuizAnswerService;
use App\Services\CoursePersonService;
use App\Services\FinalExamService;
use App\Services\ProctorEventService;
use App\Services\ProctorSessionService;
use App\Services\ProctorSnapshotService;
use App\Services\QuizAttemptScoringService;
use App\Services\QuizService;
use App\Services\R2StorageService;
use App\Support\CurrentSchool;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ProctorExamShow extends Component
{
    public Course $course;

    public Assessment $assessment;

    public Quiz $quiz;

    public FinalExamType $examType;

    /**
     * @var array<string, string>
     */
    public array $answers = [];

    public ?string $errorMessage = null;

    /**
     * Event types allowed to be logged for the current exam type, per the
     * doc's "Detection Rules by Exam Type" (Open Book doesn't flag local
     * file access; both flag network/unauthorized-app activity).
     *
     * @return array<int, string>
     */
    protected function allowedEventTypes(): array
    {
        $common = [
            ProctorEventType::TabSwitch->value,
            ProctorEventType::WindowBlur->value,
            ProctorEventType::MultipleFaces->value,
            ProctorEventType::NoFaceDetected->value,
            ProctorEventType::FaceMismatch->value,
            ProctorEventType::CopyPaste->value,
            ProctorEventType::RightClick->value,
            ProctorEventType::DevtoolsOpened->value,
            ProctorEventType::FullscreenExit->value,
            ProctorEventType::NetworkActivityDetected->value,
            ProctorEventType::UnauthorizedAppDetected->value,
        ];

        return $common;
    }

    public function mount(
        CurrentSchool $currentSchool,
        CoursePersonService $coursePersonService,
        QuizService $quizService,
        FinalExamService $finalExamService,
        ?Course $course = null,
        ?Assessment $assessment = null,
    ): void {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryFinalExam, 404);
        abort_unless(auth()->user()->hasRole(RoleName::Student), 403);
        abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);

        $finalExam = $finalExamService->findByAssessment($assessment->id);
        abort_if($finalExam === null, 404);
        abort_unless(in_array($finalExam->exam_type, [FinalExamType::OpenBook, FinalExamType::ClosedBook], true), 404);

        $quiz = $quizService->findByAssessment($assessment->id, ['questions.options']);
        abort_if($quiz === null, 404);

        $this->course = $course;
        $this->assessment = $assessment;
        $this->quiz = $quiz;
        $this->examType = $finalExam->exam_type;
    }

    public function startAttempt(AssessmentAttemptService $assessmentAttemptService, ProctorSessionService $proctorSessionService): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $inProgress = $attempts->first(fn ($attempt) => $attempt->submitted_at === null);

        if ($inProgress) {
            $this->answers = [];

            return;
        }

        if ($this->quiz->total_attempts !== null && $attempts->count() >= $this->quiz->total_attempts) {
            $this->errorMessage = __('You have reached the maximum number of attempts for this exam.');

            return;
        }

        if ($this->assessment->end_date && now()->greaterThan($this->assessment->end_date)) {
            $this->errorMessage = __('The submission window for this exam has closed.');

            return;
        }

        $attempt = $assessmentAttemptService->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => auth()->id(),
            'submitted_by' => auth()->id(),
            'attempt_number' => $attempts->count() + 1,
            'started_at' => now(),
        ]);

        $proctorSessionService->create([
            'assessment_attempt_id' => $attempt->id,
            'status' => ProctorSessionStatus::Active,
            'started_at' => now(),
        ]);

        $this->answers = [];
    }

    public function logProctorEvent(string $eventType, string $severity, ?array $metadata = null): ?string
    {
        abort_unless(in_array($eventType, $this->allowedEventTypes(), true), 422);
        ProctorEventType::from($eventType);
        ProctorSeverity::from($severity);

        $session = $this->currentSession();
        if ($session === null) {
            return null;
        }

        $event = app(ProctorEventService::class)->create([
            'proctor_session_id' => $session->id,
            'event_type' => $eventType,
            'severity' => $severity,
            'detected_at' => now(),
            'metadata' => $metadata,
        ]);

        return $event->id;
    }

    public function recordSnapshotUploaded(R2StorageService $r2StorageService, string $type, string $fileUrl, ?string $triggeredByEventId = null, ?string $capturedAt = null): void
    {
        ProctorSnapshotType::from($type);

        $session = $this->currentSession();
        if ($session === null) {
            return;
        }

        $finalKey = preg_replace('#temp/#', '', $fileUrl, 1);
        $r2StorageService->promoteFromTemp($fileUrl, $finalKey);

        app(ProctorSnapshotService::class)->create([
            'proctor_session_id' => $session->id,
            'type' => $type,
            'captured_at' => $this->resolveCapturedAt($capturedAt),
            'file_url' => $finalKey,
            'triggered_by_event_id' => $triggeredByEventId,
        ]);
    }

    protected function resolveCapturedAt(?string $capturedAt): Carbon
    {
        if ($capturedAt === null) {
            return now();
        }

        try {
            $parsed = Carbon::parse($capturedAt);
        } catch (\Exception) {
            return now();
        }

        return $parsed->between(now()->subMinutes(30), now()) ? $parsed : now();
    }

    /**
     * @return array{url: string, key: string}
     */
    public function requestSnapshotUploadUrl(R2StorageService $r2StorageService, string $filename, string $materialType = 'Image'): array
    {
        $session = $this->currentSession();
        abort_if($session === null, 404);

        return $r2StorageService->generatePresignedPutUrlForPath("proctor/{$session->id}", $filename, $materialType);
    }

    public function submitAttempt(
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentQuizAnswerService $assessmentQuizAnswerService,
        QuizAttemptScoringService $quizAttemptScoringService,
        ProctorSessionService $proctorSessionService,
    ): void {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $attempt = $attempts->first(fn ($a) => $a->submitted_at === null);

        if (! $attempt) {
            $this->errorMessage = __('No attempt in progress.');

            return;
        }

        foreach ($this->quiz->questions as $question) {
            $value = $this->answers[$question->id] ?? null;

            $isObjective = in_array($question->question_type, [QuizQuestionType::MultipleChoice, QuizQuestionType::TrueFalse], true);

            $assessmentQuizAnswerService->create([
                'assessment_attempt_id' => $attempt->id,
                'quiz_question_id' => $question->id,
                'selected_option_id' => $isObjective ? ($value ?: null) : null,
                'answer_text' => $isObjective ? null : ($value ?: null),
                'score' => $isObjective ? $quizAttemptScoringService->scoreObjectiveAnswer($question, $value ?: null) : null,
            ]);
        }

        $deadline = $this->quiz->time_limit_per_attempt
            ? $attempt->started_at->copy()->addMinutes($this->quiz->time_limit_per_attempt)
            : null;

        $assessmentAttemptService->update($attempt->id, [
            'submitted_at' => $deadline && now()->greaterThan($deadline) ? $deadline : now(),
        ]);

        $quizAttemptScoringService->recomputeForUser($this->quiz, $this->assessment->id, auth()->id());

        $session = $proctorSessionService->findByAttempt($attempt->id);
        if ($session !== null) {
            $proctorSessionService->update($session->id, [
                'status' => ProctorSessionStatus::Completed,
                'ended_at' => now(),
            ]);
        }

        $this->answers = [];

        $this->redirectRoute('assessments.final-exam.show', $this->assessment, navigate: true);
    }

    protected function currentSession()
    {
        $attempts = app(AssessmentAttemptService::class)->forAssessmentAndUser($this->assessment->id, auth()->id());
        $inProgress = $attempts->first(fn ($a) => $a->submitted_at === null);

        if ($inProgress === null) {
            return null;
        }

        return app(ProctorSessionService::class)->findByAttempt($inProgress->id);
    }

    public function render()
    {
        $assessmentAttemptService = app(AssessmentAttemptService::class);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $inProgress = $attempts->first(fn ($a) => $a->submitted_at === null);

        $canStart = ! $inProgress
            && (! $this->quiz->total_attempts || $attempts->count() < $this->quiz->total_attempts)
            && (! $this->assessment->end_date || ! $this->assessment->end_date->isPast());

        return view('livewire.courses.proctor-exam-show', [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'quiz' => $this->quiz,
            'examType' => $this->examType,
            'inProgress' => $inProgress,
            'canStart' => $canStart,
            'deadlineIso' => ($inProgress && $this->quiz->time_limit_per_attempt)
                ? $inProgress->started_at->copy()->addMinutes($this->quiz->time_limit_per_attempt)->toIso8601String()
                : null,
        ])
            ->extends('layouts.app', ['skipTopbar' => true, 'skipSidebar' => true])
            ->section('app-content');
    }
}
