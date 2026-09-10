<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentQuestionType;
use App\Enums\AssessmentType;
use App\Enums\ProctorReviewDecision;
use App\Enums\ProctorSnapshotType;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\ProctorSnapshot;
use App\Models\User;
use App\Services\AssessmentAnswerService;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionAnswerService;
use App\Services\AssessmentQuestionScoreService;
use App\Services\AssessmentScoreService;
use App\Services\FinalExamService;
use App\Services\GradebookScoringService;
use App\Services\ProctorSessionService;
use App\Services\ProctorSnapshotService;
use App\Services\R2StorageService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class AssessmentFinalExamGrade extends Component
{
    use WithPagination;

    public Course $course;

    public Assessment $assessment;

    public User $student;

    public string $gradeFeedback = '';

    public array $gradeQuestionScores = [];

    public int $questionsPerPage = 5;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    /**
     * @var array<string, string>
     */
    public array $reviewDecision = [];

    /**
     * @var array<string, string>
     */
    public array $reviewNotes = [];

    public function mount(
        CurrentSchool $currentSchool,
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentScoreService $assessmentScoreService,
        AssessmentQuestionScoreService $assessmentQuestionScoreService,
        AssessmentQuestionAnswerService $assessmentQuestionAnswerService,
        Assessment $assessment,
        User $student,
    ): void {
        $course = $assessment->course;

        abort_if($course === null, 404);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.grade') && $course->school_id === $schoolId, 403);
        abort_unless($assessment->type === AssessmentType::TheoryFinalExam, 404);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($assessment->id, $student->id)->last();
        abort_if($attempt === null, 404);

        $this->course = $course;
        $this->assessment = $assessment;
        $this->student = $student;
        $this->successMessage = session('successMessage');

        $existingScore = $assessmentScoreService->findByAttempt($attempt->id);
        $this->gradeFeedback = $existingScore ? ($existingScore->feedback ?? '') : '';

        $questionScores = $assessmentQuestionScoreService->findByAttempt($attempt->id);
        $questionAnswers = $assessmentQuestionAnswerService->forAttempt($attempt->id);

        foreach ($this->assessment->questions as $question) {
            if ($question->question_type !== AssessmentQuestionType::Essay) {
                continue;
            }

            $qAnswer = $questionAnswers->firstWhere('assessment_question_id', $question->id);
            if ($qAnswer) {
                $this->gradeQuestionScores[$question->id] = $qAnswer->score !== null ? (string) $qAnswer->score : '';

                continue;
            }

            $qScore = $questionScores->firstWhere('assessment_question_id', $question->id);
            $this->gradeQuestionScores[$question->id] = $qScore ? (string) $qScore->score : '';
        }
    }

    public function updating(string $property): void
    {
        if ($property === 'questionsPerPage') {
            $this->resetPage('questionsPage');
        }
    }

    public function submitGrade(
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentScoreService $assessmentScoreService,
        AssessmentQuestionScoreService $assessmentQuestionScoreService,
        AssessmentQuestionAnswerService $assessmentQuestionAnswerService,
        GradebookScoringService $gradebookScoringService,
    ): void {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $this->validate([
            'gradeFeedback' => 'nullable|string',
        ]);

        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $this->student->id)->last();
        abort_unless($attempt !== null, 404);

        if ($assessmentScoreService->findByAttempt($attempt->id) !== null) {
            $this->errorMessage = __('This exam has already been graded and cannot be graded again.');

            return;
        }

        $questionAnswers = $assessmentQuestionAnswerService->forAttempt($attempt->id);
        $usesAnswerPipeline = $questionAnswers->isNotEmpty();

        $gradableQuestions = $usesAnswerPipeline
            ? $this->assessment->questions->filter(fn ($question) => $question->question_type === AssessmentQuestionType::Essay)
            : $this->assessment->questions;

        // Auto-graded (non-essay) answers only — essay scores are re-added
        // fresh from the form below, so folding their already-saved score
        // in here too would double-count them on every re-save.
        $gradableQuestionIds = $gradableQuestions->pluck('id');
        $totalScore = $usesAnswerPipeline
            ? (float) $questionAnswers->reject(fn ($answer) => $gradableQuestionIds->contains($answer->assessment_question_id))->sum('score')
            : 0.0;

        foreach ($gradableQuestions as $question) {
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

        foreach ($gradableQuestions as $question) {
            $score = (float) $this->gradeQuestionScores[$question->id];

            if ($usesAnswerPipeline) {
                $answer = $questionAnswers->firstWhere('assessment_question_id', $question->id);
                if ($answer) {
                    $assessmentQuestionAnswerService->update($answer->id, ['score' => $score]);
                }

                continue;
            }

            $assessmentQuestionScoreService->updateOrCreate(
                ['assessment_attempt_id' => $attempt->id, 'assessment_question_id' => $question->id],
                ['score' => $score]
            );
        }

        $assessmentScoreService->create([
            'assessment_attempt_id' => $attempt->id,
            'score' => $totalScore,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
            'feedback' => $this->gradeFeedback ?: null,
        ]);

        $gradebookScoringService->recomputeForUser($this->course, $this->student->id);

        session()->flash('successMessage', __('Grade saved.'));

        $this->redirectRoute('assessments.final-exam.grade', ['assessment' => $this->assessment->id, 'student' => $this->student->id], navigate: false);
    }

    public function reviewProctorSession(
        string $proctorSessionId,
        ProctorSessionService $proctorSessionService,
        AssessmentScoreService $assessmentScoreService,
        AssessmentAttemptService $assessmentAttemptService,
        GradebookScoringService $gradebookScoringService,
    ): void {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $existingSession = $proctorSessionService->find($proctorSessionId);
        if ($existingSession?->reviewed_at !== null) {
            $this->errorMessage = __('This proctoring session has already been reviewed and cannot be reviewed again.');

            return;
        }

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

            $attempt = $assessmentAttemptService->find($session->assessment_attempt_id);
            if ($attempt !== null) {
                $gradebookScoringService->recomputeForUser($this->course, $attempt->user_id);
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
     * Loaded on demand when the teacher opens the screenshot review modal,
     * and re-loaded whenever they change the event-type filter, sort
     * order, or group-by-event toggle, rather than eagerly signing URLs
     * for every screenshot on page load. Filtering/sorting/pagination
     * happens in the repository at the database level.
     *
     * @return array{items: array<int, array{url: string, capturedAt: string, capturedAtEpoch: int, eventType: string, eventTypeLabel: string}>, eventTypeOptions: array<int, string>, hasMore: bool}
     */
    public function loadProctorScreenshots(
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

        $proctorSessionId = $this->resolveProctorSessionId($assessmentAttemptService, $proctorSessionService);

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
        AssessmentAttemptService $assessmentAttemptService,
        ProctorSessionService $proctorSessionService,
        ProctorSnapshotService $proctorSnapshotService,
        R2StorageService $r2StorageService,
        string $sort = 'asc',
        int $limitPerGroup = 5,
    ): array {
        abort_unless(auth()->user()->can('assessment.grade'), 403);

        $proctorSessionId = $this->resolveProctorSessionId($assessmentAttemptService, $proctorSessionService);

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

    private function resolveProctorSessionId(AssessmentAttemptService $assessmentAttemptService, ProctorSessionService $proctorSessionService): string
    {
        $latest = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $this->student->id)->last();
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

    public function render(
        AssessmentAttemptService $assessmentAttemptService,
        AssessmentAnswerService $assessmentAnswerService,
        AssessmentQuestionAnswerService $assessmentQuestionAnswerService,
        AssessmentScoreService $assessmentScoreService,
        FinalExamService $finalExamService,
        ProctorSessionService $proctorSessionService,
    ) {
        $attempt = $assessmentAttemptService->forAssessmentAndUser($this->assessment->id, $this->student->id)->last();
        $answer = $attempt ? $assessmentAnswerService->findByAttempt($attempt->id) : null;
        $questionAnswers = $attempt ? $assessmentQuestionAnswerService->forAttempt($attempt->id) : collect();
        $finalScore = $attempt ? $assessmentScoreService->findByAttempt($attempt->id) : null;

        $finalExam = $finalExamService->findByAssessment($this->assessment->id);
        $isProctored = $finalExam && in_array($finalExam->exam_type->value, ['open_book', 'closed_book'], true);
        $proctorSession = ($isProctored && $attempt)
            ? $proctorSessionService->findByAttempt($attempt->id, ['events', 'snapshots'])
            : null;

        $recordings = $proctorSession
            ? $proctorSession->snapshots->where('type', ProctorSnapshotType::Recording)->sortBy('captured_at')
            : collect();

        $screenshotsCount = $proctorSession
            ? $proctorSession->snapshots->where('type', ProctorSnapshotType::Screen)->count()
            : 0;

        $questions = $this->assessment->questions->loadMissing('options');
        $questionsPage = $this->getPage('questionsPage');

        $paginatedQuestions = new LengthAwarePaginator(
            $questions->forPage($questionsPage, $this->questionsPerPage)->values(),
            $questions->count(),
            $this->questionsPerPage,
            $questionsPage,
            ['path' => request()->url(), 'pageName' => 'questionsPage']
        );

        $mcQuestions = $questions->filter(fn ($question) => $question->question_type === AssessmentQuestionType::MultipleChoice);
        $keyedAnswers = $questionAnswers->keyBy('assessment_question_id');
        $mcScore = [
            'count' => $mcQuestions->count(),
            'correct' => $mcQuestions->filter(function ($question) use ($keyedAnswers) {
                $answer = $keyedAnswers->get($question->id);

                return $answer && $answer->selected_option_id === optional($question->options->firstWhere('is_correct', true))->id;
            })->count(),
            'earned' => $mcQuestions->sum(fn ($question) => optional($keyedAnswers->get($question->id))->score ?? 0),
            'possible' => $mcQuestions->sum('points'),
        ];

        return view('livewire.courses.assessment-final-exam-grade', [
            'course' => $this->course,
            'assessment' => $this->assessment,
            'student' => $this->student,
            'attempt' => $attempt,
            'answer' => $answer,
            'questionAnswers' => $keyedAnswers,
            'paginatedQuestions' => $paginatedQuestions,
            'mcScore' => $mcScore,
            'finalScore' => $finalScore,
            'alreadyGraded' => $finalScore !== null,
            'alreadyReviewed' => $proctorSession !== null && $proctorSession->reviewed_at !== null,
            'isProctored' => $isProctored,
            'proctorSession' => $proctorSession,
            'cameraRecordings' => $recordings->filter(fn ($s) => str_contains($s->file_url, 'webcam-recording'))->values(),
            'screenRecordings' => $recordings->filter(fn ($s) => str_contains($s->file_url, 'screen-recording'))->values(),
            'screenshotsCount' => $screenshotsCount,
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
