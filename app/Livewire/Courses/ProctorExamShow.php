<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\ProctorEventType;
use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSessionStatus;
use App\Enums\ProctorSeverity;
use App\Enums\ProctorSnapshotType;
use App\Enums\RoleName;
use App\Jobs\FinalizeExamSubmissionJob;
use App\Jobs\FinalizeProctorDisqualificationJob;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\Quiz;
use App\Models\Session;
use App\Services\AssessmentAttemptService;
use App\Services\CoursePersonService;
use App\Services\FinalExamService;
use App\Services\ProctorEventService;
use App\Services\ProctorSessionService;
use App\Services\ProctorSessionStatusService;
use App\Services\ProctorSnapshotService;
use App\Services\QuizService;
use App\Services\R2StorageService;
use App\Services\SessionService;
use App\Support\CurrentSchool;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Livewire\Attributes\Renderless;
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
     * Event types allowed to be logged. Open book and closed book exams
     * use identical violation rules.
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

    /**
     * Flags the proctor session as submitting in Redis immediately (before
     * any of the client's slow evidence-upload/cleanup work), then runs the
     * actual finalization synchronously. Mirrors beginDisqualification() so
     * a refresh mid-submit can't leave the attempt answerable again.
     *
     * Renderless: this is called mid-way through the client's own cleanup
     * (finishSubmit() still needs to stop the recorder and upload evidence
     * afterwards). A normal render here would morph the exam DOM to the
     * "submitting" branch immediately, tearing down the very Alpine
     * component/MediaRecorder instances that cleanup is still running on.
     */
    #[Renderless]
    public function beginSubmission(
        AssessmentAttemptService $assessmentAttemptService,
        ProctorSessionService $proctorSessionService,
    ): void {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $attempt = $attempts->first(fn ($a) => $a->submitted_at === null);

        if (! $attempt) {
            $this->errorMessage = __('No attempt in progress.');

            return;
        }

        $session = $proctorSessionService->findByAttempt($attempt->id);

        if ($session === null || $session->status !== ProctorSessionStatus::Active || $proctorSessionService->isSubmitting($session->id)) {
            return;
        }

        $proctorSessionService->markSubmitting($session->id);

        FinalizeExamSubmissionJob::dispatchSync($attempt->id);
    }

    /**
     * Flags the proctor session as submitting in Redis immediately (before
     * any of the client's slow evidence-upload work), then runs the actual
     * finalization synchronously (dispatchSync), independent of whether a
     * queue worker is running. This is the fast step that closes the
     * window where a page refresh could let a disqualified student keep
     * answering the exam: from this point on, `submitting` is true in
     * render() regardless of how long finalization takes to run.
     *
     * Renderless: see beginSubmission() — the client still needs to finish
     * uploading evidence snapshots and stopping the recorder afterwards, so
     * this must not morph the exam DOM away mid-cleanup.
     */
    #[Renderless]
    public function beginDisqualification(
        AssessmentAttemptService $assessmentAttemptService,
        ProctorSessionService $proctorSessionService,
        string $reason,
    ): void {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $attempt = $attempts->first(fn ($a) => $a->submitted_at === null);

        if (! $attempt) {
            return;
        }

        $session = $proctorSessionService->findByAttempt($attempt->id);

        if ($session === null || $session->status !== ProctorSessionStatus::Active || $proctorSessionService->isSubmitting($session->id)) {
            return;
        }

        $proctorSessionService->markSubmitting($session->id);

        FinalizeProctorDisqualificationJob::dispatchSync($attempt->id, $reason);
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

    /**
     * @return array{id: string, title: string, type: string, icon: string, isImage: bool, url: string|null, extension: string|null, sessionId: string, sessionTitle: string}
     */
    private function toMaterialPayload(MediaLibraryItem $material, Session $session): array
    {
        return [
            'id' => (string) $material->id,
            'title' => $material->title,
            'type' => $material->type->value,
            'icon' => $material->type->icon(),
            'isImage' => $material->type->value === 'Image',
            'url' => $material->file_url,
            'extension' => $material->file_path ? strtolower(pathinfo($material->file_path, PATHINFO_EXTENSION)) : null,
            'sessionId' => (string) $session->id,
            'sessionTitle' => $session->title,
        ];
    }

    public function render(SessionService $sessionService, ProctorSessionStatusService $disqualificationStatusService, ProctorSessionService $proctorSessionService)
    {
        $assessmentAttemptService = app(AssessmentAttemptService::class);

        $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $inProgress = $attempts->first(fn ($a) => $a->submitted_at === null);

        $latestSession = $disqualificationStatusService->latestSessionForAssessment($this->assessment->id, auth()->id());
        $submitting = $latestSession !== null && $proctorSessionService->isSubmitting($latestSession->id);
        $disqualified = $latestSession?->review_decision === ProctorReviewDecision::Disqualified;
        $justSubmitted = $latestSession?->status === ProctorSessionStatus::Completed;

        if ($submitting) {
            $inProgress = null;
        }

        $canStart = ! $inProgress
            && ! $submitting
            && (! $this->quiz->total_attempts || $attempts->count() < $this->quiz->total_attempts)
            && (! $this->assessment->end_date || ! $this->assessment->end_date->isPast());

        $examMaterials = [];
        $examSessions = [];

        if ($this->examType === FinalExamType::OpenBook) {
            $sessions = $sessionService->forCourse($this->course->id, ['materials']);

            $examSessions = $sessions
                ->filter(fn (Session $session) => $session->materials->isNotEmpty())
                ->map(fn (Session $session) => ['id' => (string) $session->id, 'title' => $session->title])
                ->values()
                ->all();

            $examMaterials = $sessions
                ->flatMap(fn (Session $session) => $session->materials->map(
                    fn (MediaLibraryItem $material) => $this->toMaterialPayload($material, $session)
                )->all())
                ->unique('id')
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
            'justSubmitted' => $justSubmitted,
            'submitting' => $submitting,
            'disqualified' => $disqualified,
            'examMaterials' => $examMaterials,
            'examSessions' => $examSessions,
            'deadlineIso' => ($inProgress && $this->quiz->time_limit_per_attempt)
                ? $inProgress->started_at->copy()->addMinutes($this->quiz->time_limit_per_attempt)->toIso8601String()
                : null,
        ])
            ->extends('layouts.app', ['skipTopbar' => true, 'skipSidebar' => true])
            ->section('app-content');
    }
}
