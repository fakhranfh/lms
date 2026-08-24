<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\FinalExamType;
use App\Enums\MaterialType;
use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSnapshotType;
use App\Enums\RoleName;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\ExamReferenceFile;
use App\Models\ProctorSnapshot;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionScoreService;
use App\Services\AssessmentScoreService;
use App\Services\CoursePersonService;
use App\Services\ExamReferenceFileService;
use App\Services\FinalExamService;
use App\Services\ProctorSessionService;
use App\Services\ProctorSnapshotService;
use App\Services\R2StorageService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class AssessmentFinalExamShow extends Component
{
    use WithRichTextEditor;

    public Course $course;

    public Assessment $assessment;

    public bool $isStudent = false;

    public string $answerText = '';

    public ?string $gradingUserId = null;

    public string $gradeScore = '';

    public string $gradeFeedback = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public ?string $referenceFileError = null;

    public array $gradeQuestionScores = [];

    /**
     * @var array<string, string>
     */
    public array $reviewDecision = [];

    /**
     * @var array<string, string>
     */
    public array $reviewNotes = [];

    public function mount(CurrentSchool $currentSchool, CoursePersonService $coursePersonService, ?Course $course = null, ?Assessment $assessment = null): void
    {
        abort_if($assessment === null, 404);

        $course ??= $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->course_id === $course->id, 404);
        abort_unless($assessment->type === AssessmentType::TheoryFinalExam, 404);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);

        if ($this->isStudent) {
            abort_unless($coursePersonService->isEnrolledAsStudent($course->id, auth()->id()), 403);
        }

        $this->course = $course;
        $this->assessment = $assessment;
    }

    public function submit(AssessmentAttemptService $assessmentAttemptService, AssessmentAnswerService $assessmentAnswerService): bool
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $this->validate([
            'answerText' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if (trim(strip_tags($value)) === '') {
                    $fail(__('Answer cannot be empty.'));
                }
            }],
        ]);

        $previousAttempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
        $latest = $previousAttempts->last();

        if ($latest && $latest->score) {
            $this->errorMessage = __('This final exam has already been graded and can no longer be resubmitted.');

            return false;
        }

        if ($this->assessment->end_date && now()->greaterThan($this->assessment->end_date)) {
            $this->errorMessage = __('The submission window for this final exam has closed.');

            return false;
        }

        $attempt = $assessmentAttemptService->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => auth()->id(),
            'submitted_by' => auth()->id(),
            'attempt_number' => $latest ? $latest->attempt_number + 1 : 1,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $assessmentAnswerService->create([
            'assessment_attempt_id' => $attempt->id,
            'answer_text' => HtmlSanitizer::forum($this->promoteRichTextAttachments($this->answerText)),
        ]);

        $this->answerText = '';
        $this->successMessage = __('Your submission has been recorded.');

        return true;
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    private function assertOpenBook(FinalExamService $finalExamService): void
    {
        $finalExam = $finalExamService->findByAssessment($this->assessment->id);
        abort_unless($finalExam?->exam_type === FinalExamType::OpenBook, 403);
    }

    /**
     * @return array<string, string>
     */
    private function referenceExtensionToTypeMap(): array
    {
        $map = [];
        foreach (MaterialType::cases() as $type) {
            foreach ($type->allowedExtensions() as $extension) {
                $map[$extension] = $type->value;
            }
        }

        return $map;
    }

    /**
     * Renderless: this is fired for every file the student picks, often
     * several in flight at once. A normal render here would morph the DOM
     * mid-upload, which can visibly disturb the Alpine-rendered progress
     * grid (tiles flickering) while requests are still in flight.
     *
     * @return array{url?: string, key?: string, error?: string}
     */
    #[Renderless]
    public function generateReferenceFileUploadUrl(string $filename, string $materialType, FinalExamService $finalExamService, ExamReferenceFileService $examReferenceFileService): array
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);
        $this->assertOpenBook($finalExamService);

        if (MaterialType::tryFrom($materialType) === null) {
            return ['error' => 'Invalid material type'];
        }

        try {
            return $examReferenceFileService->generatePresignedUploadUrl($this->assessment->id, auth()->id(), $filename, $materialType);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @return array{id: string, title: string, type: string, icon: string, url: string|null, extension: string|null}
     */
    private function toReferenceFilePayload(ExamReferenceFile $file): array
    {
        return [
            'id' => (string) $file->id,
            'title' => $file->title,
            'type' => $file->type->value,
            'icon' => $file->type->icon(),
            'url' => $file->file_url,
            'extension' => $file->file_path ? strtolower(pathinfo($file->file_path, PATHINFO_EXTENSION)) : null,
        ];
    }

    /**
     * Renderless — see generateReferenceFileUploadUrl().
     *
     * @param  array<string, mixed>  $data
     * @return array{error?: string, file?: array{id: string, title: string, type: string, icon: string, url: string|null, extension: string|null}}
     */
    #[Renderless]
    public function finalizeReferenceFileUpload(array $data, FinalExamService $finalExamService, ExamReferenceFileService $examReferenceFileService): array
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);
        $this->assertOpenBook($finalExamService);

        try {
            $file = $examReferenceFileService->finalizeUpload($this->assessment->id, auth()->id(), $data);
            $this->referenceFileError = null;

            return ['file' => $this->toReferenceFilePayload($file)];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Renderless — see generateReferenceFileUploadUrl(). The deleted tile is
     * already removed client-side once this resolves, so a render here
     * would only risk morphing the rest of the upload grid mid-interaction.
     */
    #[Renderless]
    public function deleteReferenceFile(string $id, ExamReferenceFileService $examReferenceFileService): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $examReferenceFileService->delete($id, auth()->id());
    }

    /**
     * Renderless — see generateReferenceFileUploadUrl(). The grid is cleared
     * client-side once this resolves.
     */
    #[Renderless]
    public function deleteAllReferenceFiles(ExamReferenceFileService $examReferenceFileService): void
    {
        abort_unless(auth()->user()->can('assessment.submit'), 403);

        $examReferenceFileService->deleteAllForAssessmentAndUser($this->assessment->id, auth()->id());
    }

    public function reviewProctorSession(
        string $proctorSessionId,
        ProctorSessionService $proctorSessionService,
        AssessmentScoreService $assessmentScoreService,
    ): void {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $decisionEnum = ProctorReviewDecision::from($this->reviewDecision[$proctorSessionId] ?? 'no_action');
        $notes = $this->reviewNotes[$proctorSessionId] ?? null;

        $session = $proctorSessionService->update($proctorSessionId, [
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_decision' => $decisionEnum,
            'review_notes' => $notes,
        ]);

        if ($decisionEnum === ProctorReviewDecision::Disqualified) {
            $score = $assessmentScoreService->findByAttempt($session->assessment_attempt_id);

            if ($score) {
                $assessmentScoreService->update($score->id, ['score' => 0]);
            } else {
                $assessmentScoreService->create([
                    'assessment_attempt_id' => $session->assessment_attempt_id,
                    'score' => 0,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                    'feedback' => __('Disqualified due to proctoring violation.'),
                ]);
            }
        }

        $this->successMessage = __('Proctoring review saved.');
    }

    public function recordingUrl(string $key): string
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);
        abort_unless(str_contains($key, '/proctor/'), 403);

        return app(R2StorageService::class)->getSignedUrl($key, 3600);
    }

    /**
     * Loaded on demand when the teacher opens the screenshot review modal
     * for a student, and re-loaded whenever they change the event-type
     * filter, sort order, or group-by-event toggle, rather than eagerly
     * signing URLs for every student's every screenshot on every page
     * load. Filtering/sorting/pagination happens in the repository at
     * the database level — nothing is loaded into memory beyond the
     * current page.
     *
     * @return array{items: array<int, array{url: string, capturedAt: string, capturedAtEpoch: int, eventType: string, eventTypeLabel: string}>, eventTypeOptions: array<int, string>, hasMore: bool}
     */
    public function loadProctorScreenshots(
        string $userId,
        AssessmentAttemptService $assessmentAttemptService,
        ProctorSessionService $proctorSessionService,
        ProctorSnapshotService $proctorSnapshotService,
        R2StorageService $r2StorageService,
        ?string $eventType = null,
        string $sort = 'asc',
        int $offset = 0,
        int $limit = 5,
    ): array {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $proctorSessionId = $this->resolveProctorSessionId($userId, $assessmentAttemptService, $proctorSessionService);

        $page = $proctorSnapshotService->paginateScreenshotsForSession($proctorSessionId, $eventType, $sort, $offset, $limit);

        return [
            'items' => $page['items']->map(fn ($shot) => $this->formatProctorScreenshotItem($shot, $r2StorageService))->values()->all(),
            'eventTypeOptions' => $proctorSnapshotService->screenshotEventTypesForSession($proctorSessionId),
            'hasMore' => ($offset + $limit) < $page['total'],
        ];
    }

    /**
     * Groups screenshots by event type (counts computed from the full set
     * in the repository, so a group's badge count is always accurate),
     * then loads only each group's first N items. loadProctorScreenshots()
     * handles paginating further within a group.
     *
     * @return array{groups: array<int, array{eventType: string, label: string, total: int, hasMore: bool, items: array<int, array{url: string, capturedAt: string, capturedAtEpoch: int, eventType: string, eventTypeLabel: string}>}>, eventTypeOptions: array<int, string>}
     */
    public function loadProctorScreenshotGroups(
        string $userId,
        AssessmentAttemptService $assessmentAttemptService,
        ProctorSessionService $proctorSessionService,
        ProctorSnapshotService $proctorSnapshotService,
        R2StorageService $r2StorageService,
        string $sort = 'asc',
        int $limitPerGroup = 5,
    ): array {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $proctorSessionId = $this->resolveProctorSessionId($userId, $assessmentAttemptService, $proctorSessionService);

        $eventTypeOptions = $proctorSnapshotService->screenshotEventTypesForSession($proctorSessionId);

        $groups = collect($eventTypeOptions)->map(function ($eventType) use ($proctorSessionId, $proctorSnapshotService, $r2StorageService, $sort, $limitPerGroup) {
            $page = $proctorSnapshotService->paginateScreenshotsForSession($proctorSessionId, $eventType, $sort, 0, $limitPerGroup);

            return [
                'eventType' => $eventType,
                'label' => $eventType === 'none' ? 'Other' : str($eventType)->replace('_', ' ')->title()->toString(),
                'total' => $page['total'],
                'hasMore' => $limitPerGroup < $page['total'],
                'items' => $page['items']->map(fn ($shot) => $this->formatProctorScreenshotItem($shot, $r2StorageService))->values()->all(),
            ];
        })->values()->all();

        return [
            'groups' => $groups,
            'eventTypeOptions' => $eventTypeOptions,
        ];
    }

    private function resolveProctorSessionId(string $userId, AssessmentAttemptService $assessmentAttemptService, ProctorSessionService $proctorSessionService): string
    {
        $latest = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $userId)->last();
        abort_if($latest === null, 404);

        $proctorSession = $proctorSessionService->findByAttempt($latest->id);
        abort_if($proctorSession === null, 404);

        return $proctorSession->id;
    }

    /**
     * @return array{url: string, cameraUrl: ?string, capturedAt: string, capturedAtEpoch: int, eventType: string, eventTypeLabel: string}
     */
    private function formatProctorScreenshotItem(ProctorSnapshot $shot, R2StorageService $r2StorageService): array
    {
        $eventType = $shot->triggeredByEvent?->event_type->value ?? 'none';
        $pairedWebcam = $shot->relationLoaded('pairedWebcam') ? $shot->getRelation('pairedWebcam') : null;

        return [
            'url' => $r2StorageService->getSignedUrl($shot->file_url, 3600),
            'cameraUrl' => $pairedWebcam ? $r2StorageService->getSignedUrl($pairedWebcam->file_url, 3600) : null,
            'capturedAt' => $shot->captured_at_display->format('M j, Y H:i:s'),
            'capturedAtEpoch' => $shot->captured_at_display->timestamp,
            'eventType' => $eventType,
            'eventTypeLabel' => $eventType === 'none' ? 'Other' : str($eventType)->replace('_', ' ')->title()->toString(),
        ];
    }

    public function openGrading(string $userId, AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionScoreService $assessmentQuestionScoreService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $userId)->last();

        if (! $attempt) {
            return;
        }

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);
        $questionScores = $assessmentQuestionScoreService->findByAttempt($attempt->id);

        $this->gradingUserId = $userId;
        $this->gradeFeedback = $existingScore ? ($existingScore->feedback ?? '') : '';

        $this->gradeQuestionScores = [];
        foreach ($this->assessment->questions as $question) {
            $qScore = $questionScores->firstWhere('assessment_question_id', $question->id);
            $this->gradeQuestionScores[$question->id] = $qScore ? (string) $qScore->score : '';
        }
    }

    public function cancelGrading(): void
    {
        $this->gradingUserId = null;
        $this->gradeScore = '';
        $this->gradeFeedback = '';
    }

    public function submitGrade(AssessmentAttemptService $assessmentAttemptService, AssessmentScoreService $assessmentScoreService, AssessmentQuestionScoreService $assessmentQuestionScoreService): void
    {
        abort_unless(auth()->user()->can('assessment.grade'), 403);
        abort_unless($this->gradingUserId !== null, 404);

        $this->validate([
            'gradeFeedback' => 'nullable|string',
        ]);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $this->gradingUserId)->last();
        abort_unless($attempt !== null, 404);

        $totalScore = 0;
        foreach ($this->assessment->questions as $question) {
            $score = $this->gradeQuestionScores[$question->id] ?? '';
            if ($score === '') {
                $this->addError("gradeQuestionScores.{$question->id}", __('Score is required'));

                continue;
            }

            if (! is_numeric($score) || (float) $score < 0 || (float) $score > $question->points) {
                $this->addError("gradeQuestionScores.{$question->id}", __('Score must be between 0 and '.$question->points));

                continue;
            }

            $totalScore += (float) $score;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        foreach ($this->assessment->questions as $question) {
            $score = (float) $this->gradeQuestionScores[$question->id];
            $assessmentQuestionScoreService->updateOrCreate(
                ['assessment_attempt_id' => $attempt->id, 'assessment_question_id' => $question->id],
                ['score' => $score]
            );
        }

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);
        $data = [
            'assessment_attempt_id' => $attempt->id,
            'score' => $totalScore,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
            'feedback' => $this->gradeFeedback ?: null,
        ];

        if ($existingScore) {
            $assessmentScoreService->update($existingScore->id, $data);
        } else {
            $assessmentScoreService->create($data);
        }

        $this->cancelGrading();
        $this->successMessage = __('Grade saved.');
    }

    public function render(
        CoursePersonService $coursePersonService,
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentAnswerService $assessmentAnswerService,
        AssessmentScoreService $assessmentScoreService,
        AssessmentQuestionScoreService $assessmentQuestionScoreService,
        FinalExamService $finalExamService,
        ProctorSessionService $proctorSessionService,
        ExamReferenceFileService $examReferenceFileService,
    ) {
        $isExpired = $this->assessment->end_date && $this->assessment->end_date->isPast();
        $finalExam = $finalExamService->findByAssessment($this->assessment->id);
        $isProctored = $finalExam && in_array($finalExam->exam_type->value, ['open_book', 'closed_book']);
        $isOpenBook = $finalExam?->exam_type === FinalExamType::OpenBook;

        $viewData = [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'finalExam' => $finalExam,
            'isProctored' => $isProctored,
            'isOpenBook' => $isOpenBook,
            'isStudent' => $this->isStudent,
            'canGrade' => auth()->user()->can('assessment.grade'),
            'canSubmit' => auth()->user()->can('assessment.submit'),
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
            'isExpired' => $isExpired,
            'referenceFiles' => [],
            'referenceExtensionTypeMap' => [],
            'referenceAcceptedExtensions' => '',
            'referenceFileError' => $this->referenceFileError,
        ];

        if ($this->isStudent) {
            $allAttempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, auth()->id());
            $latest = $allAttempts->last();

            $isInProgress = $latest !== null && $latest->submitted_at === null;

            $attemptLimit = $this->assessment->attempt_limit;
            $attemptsUsed = $allAttempts->count();
            $canResubmit = ! $latest?->score && (! $this->assessment->end_date || now()->lessThanOrEqualTo($this->assessment->end_date));
            if ($attemptLimit && $attemptsUsed >= $attemptLimit && ! $isInProgress) {
                $canResubmit = false;
            }

            $latestScore = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
            $latestProctorSession = ($isProctored && $latest) ? $proctorSessionService->findByAttempt($latest->id) : null;

            $viewData['latestAttempt'] = $latest;
            $viewData['latestScore'] = $latestScore;
            $viewData['latestProctorSession'] = $latestProctorSession;
            $viewData['pendingProctorReview'] = $latestProctorSession !== null && $latestProctorSession->reviewed_at === null && ! $isInProgress;
            $viewData['isDisqualified'] = $latestProctorSession?->review_decision === ProctorReviewDecision::Disqualified;
            $viewData['canResubmit'] = $canResubmit;
            $viewData['isInProgress'] = $isInProgress;
            $viewData['attemptLimit'] = $attemptLimit ? (string) $attemptLimit : 'Unlimited';
            $viewData['attemptsUsed'] = $attemptsUsed;

            if ($isOpenBook) {
                $extensionTypeMap = $this->referenceExtensionToTypeMap();

                $viewData['referenceFiles'] = $examReferenceFileService->forAssessmentAndUser($this->assessment->id, auth()->id())
                    ->map(fn (ExamReferenceFile $file) => $this->toReferenceFilePayload($file))
                    ->values()
                    ->all();
                $viewData['referenceExtensionTypeMap'] = $extensionTypeMap;
                $viewData['referenceAcceptedExtensions'] = implode(',', array_map(fn (string $ext) => ".{$ext}", array_keys($extensionTypeMap)));
            }
        } else {
            $students = $coursePersonService->studentsForCourse($this->course->id);

            $rows = $students->map(function ($coursePerson) use ($assessmentAttemptService, $assessmentAnswerService, $assessmentScoreService, $assessmentQuestionScoreService, $proctorSessionService, $isProctored) {
                $attempts = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $coursePerson->user_id);
                $latest = $attempts->last();
                $score = $latest ? $assessmentScoreService->findByAttempt($latest->id) : null;
                $answer = $latest ? $assessmentAnswerService->findByAttempt($latest->id) : null;
                $questionScores = $latest ? $assessmentQuestionScoreService->findByAttempt($latest->id) : collect();
                $proctorSession = ($isProctored && $latest)
                    ? $proctorSessionService->findByAttempt($latest->id, ['events', 'snapshots'])
                    : null;

                $recordings = $proctorSession
                    ? $proctorSession->snapshots->where('type', ProctorSnapshotType::Recording)->sortBy('captured_at')
                    : collect();

                $isInProgress = $latest !== null && $latest->submitted_at === null;
                $pendingProctorReview = $proctorSession !== null && $proctorSession->reviewed_at === null && ! $isInProgress;

                // Screenshot count only — no signed URLs generated here. The
                // actual items (with signed URLs) are loaded on demand via
                // loadProctorScreenshots() when the teacher opens the modal,
                // so we're not signing URLs for every student's every
                // screenshot on every page load.
                $screenshotsCount = $proctorSession
                    ? $proctorSession->snapshots->where('type', ProctorSnapshotType::Screen)->count()
                    : 0;

                return [
                    'user' => $coursePerson->user,
                    'attempt' => $latest,
                    'answer' => $answer,
                    'score' => $score,
                    'questionScores' => $questionScores->keyBy('assessment_question_id'),
                    'proctorSession' => $proctorSession,
                    'pendingProctorReview' => $pendingProctorReview,
                    'cameraRecordings' => $recordings->filter(fn ($s) => str_contains($s->file_url, 'webcam-recording'))->values(),
                    'screenRecordings' => $recordings->filter(fn ($s) => str_contains($s->file_url, 'screen-recording'))->values(),
                    'screenshotsCount' => $screenshotsCount,
                ];
            })->values();

            $viewData['studentRows'] = $rows;
        }

        return view('livewire.courses.assessment-final-exam-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
