<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\MaterialType;
use App\Enums\ProctorEventType;
use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSessionStatus;
use App\Enums\ProctorSeverity;
use App\Enums\ProctorSnapshotType;
use App\Enums\QuizQuestionType;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\Quiz;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuizAnswerService;
use App\Services\AssessmentScoreService;
use App\Services\CoursePersonService;
use App\Services\FinalExamService;
use App\Services\ProctorEventService;
use App\Services\ProctorSessionService;
use App\Services\ProctorSnapshotService;
use App\Services\QuizAttemptScoringService;
use App\Services\QuizService;
use App\Services\R2StorageService;
use App\Services\SessionService;
use App\Support\CurrentSchool;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
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

    public bool $justSubmitted = false;

    public bool $disqualified = false;

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
            ProctorEventType::NavigationAttempt->value,
            ProctorEventType::NetworkActivityDetected->value,
            ProctorEventType::UnauthorizedAppDetected->value,
            ProctorEventType::ReadingSuspected->value,
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

        $inProgress = app(AssessmentAttemptService::class)->forAssessmentAndUser($assessment->id, auth()->id())
            ->first(fn ($attempt) => $attempt->submitted_at === null);

        if ($inProgress !== null) {
            $this->answers = $this->loadSavedAnswers($inProgress->id);
        }
    }

    protected function answersRedisKey(string $attemptId): string
    {
        return "proctor_exam_answers:{$attemptId}";
    }

    /**
     * @return array<string, string>
     */
    protected function loadSavedAnswers(string $attemptId): array
    {
        return Redis::hgetall($this->answersRedisKey($attemptId)) ?: [];
    }

    public function updated(string $name, mixed $value): void
    {
        if (! str_starts_with($name, 'answers.')) {
            return;
        }

        $session = $this->currentSession();
        if ($session === null) {
            return;
        }

        $questionId = substr($name, strlen('answers.'));

        Redis::hset($this->answersRedisKey($session->assessment_attempt_id), $questionId, (string) $value);
    }

    public function startAttempt(AssessmentAttemptService $assessmentAttemptService, ProctorSessionService $proctorSessionService): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $inProgress = $attempts->first(fn ($attempt) => $attempt->submitted_at === null);

        if ($inProgress) {
            $this->answers = $this->loadSavedAnswers($inProgress->id);

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

        try {
            $r2StorageService->promoteFromTemp($fileUrl, $finalKey);
        } catch (\Throwable $e) {
            // Evidence capture is best-effort: a snapshot upload that never
            // landed in R2 (dropped connection, page unload mid-upload, etc.)
            // shouldn't crash the exam-taking request.
            report($e);

            return;
        }

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

        Redis::del($this->answersRedisKey($attempt->id));

        $this->answers = [];
        $this->justSubmitted = true;
    }

    public function disqualifyAttempt(
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentScoreService $assessmentScoreService,
        ProctorSessionService $proctorSessionService,
        string $reason,
    ): void {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $attempt = $attempts->first(fn ($a) => $a->submitted_at === null);

        if (! $attempt) {
            return;
        }

        $feedback = __('Disqualified: cheating detected during the exam (:reason).', ['reason' => $reason]);

        $assessmentAttemptService->update($attempt->id, ['submitted_at' => now()]);

        $score = $assessmentScoreService->findByAttempt($attempt->id);
        if ($score) {
            $assessmentScoreService->update($score->id, ['score' => 0, 'feedback' => $feedback]);
        } else {
            $assessmentScoreService->create([
                'assessment_attempt_id' => $attempt->id,
                'score' => 0,
                'graded_at' => now(),
                'feedback' => $feedback,
            ]);
        }

        $session = $proctorSessionService->findByAttempt($attempt->id);
        if ($session !== null) {
            $proctorSessionService->update($session->id, [
                'status' => ProctorSessionStatus::Terminated,
                'ended_at' => now(),
                'review_decision' => ProctorReviewDecision::Disqualified,
                'reviewed_at' => now(),
                'review_notes' => $feedback,
            ]);
        }

        Redis::del($this->answersRedisKey($attempt->id));

        $this->answers = [];
        $this->errorMessage = $feedback;
        $this->disqualified = true;
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

    private function getMaterialIcon(MaterialType $type): string
    {
        return match ($type) {
            MaterialType::Video => '🎥',
            MaterialType::PDF => '📄',
            MaterialType::Document => '📝',
            MaterialType::Audio => '🎵',
            MaterialType::Presentation => '📊',
            MaterialType::Image => '🖼️',
            MaterialType::Interactive => '🎮',
            MaterialType::Markdown => '📄',
        };
    }

    /**
     * @return array{id: string, title: string, type: string, icon: string, isImage: bool, url: string|null, extension: string|null}
     */
    private function toMaterialPayload(MediaLibraryItem $material): array
    {
        return [
            'id' => (string) $material->id,
            'title' => $material->title,
            'type' => $material->type->value,
            'icon' => $this->getMaterialIcon($material->type),
            'isImage' => $material->type->value === 'Image',
            'url' => $material->file_url,
            'extension' => $material->file_path ? strtolower(pathinfo($material->file_path, PATHINFO_EXTENSION)) : null,
        ];
    }

    public function render(SessionService $sessionService)
    {
        $assessmentAttemptService = app(AssessmentAttemptService::class);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $inProgress = $attempts->first(fn ($a) => $a->submitted_at === null);

        $canStart = ! $inProgress
            && (! $this->quiz->total_attempts || $attempts->count() < $this->quiz->total_attempts)
            && (! $this->assessment->end_date || ! $this->assessment->end_date->isPast());

        $examMaterials = [];

        if ($this->examType === FinalExamType::OpenBook) {
            $examMaterials = $sessionService->forCourse($this->course->id, ['materials'])
                ->flatMap->materials
                ->unique('id')
                ->map(fn (MediaLibraryItem $material) => $this->toMaterialPayload($material))
                ->values()
                ->all();
        }

        return view('livewire.courses.proctor-exam-show', [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'quiz' => $this->quiz,
            'examType' => $this->examType,
            'inProgress' => $inProgress,
            'canStart' => $canStart,
            'justSubmitted' => $this->justSubmitted,
            'disqualified' => $this->disqualified,
            'examMaterials' => $examMaterials,
            'deadlineIso' => ($inProgress && $this->quiz->time_limit_per_attempt)
                ? $inProgress->started_at->copy()->addMinutes($this->quiz->time_limit_per_attempt)->toIso8601String()
                : null,
        ])
            ->extends('layouts.app', ['skipTopbar' => true, 'skipSidebar' => true])
            ->section('app-content');
    }
}
