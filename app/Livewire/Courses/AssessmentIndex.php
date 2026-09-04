<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\ProctorReviewDecision;
use App\Enums\RoleName;
use App\Livewire\Concerns\WithDevMaterialAttachments;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\GroupMember;
use App\Models\Session;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentQuestionService;
use App\Services\AssessmentService;
use App\Services\AttendanceDerivationService;
use App\Services\AttendanceScoringService;
use App\Services\CoursePersonService;
use App\Services\ForumDiscussionScoringService;
use App\Services\GroupMemberService;
use App\Services\MediaLibraryService;
use App\Services\ProctorSessionService;
use App\Services\QuizAttemptScoringService;
use App\Services\R2StorageService;
use App\Support\AssessmentTypeLabel;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Support\Collection;
use Livewire\Component;

class AssessmentIndex extends Component
{
    use WithDevMaterialAttachments;

    public Course $course;

    public bool $isStudent = false;

    public bool $assessmentsLoaded = false;

    public ?string $errorMessage = null;

    public array $expandedSections = [];

    public string $generateCount = '5';

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('assessment.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function loadAssessments(): void
    {
        $this->assessmentsLoaded = true;
    }

    public function toggleSection(string $sectionKey): void
    {
        if (isset($this->expandedSections[$sectionKey])) {
            unset($this->expandedSections[$sectionKey]);
        } else {
            $this->expandedSections[$sectionKey] = true;
        }
    }

    public function deleteAssessment(string $assessmentId, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.delete'), 403);

        $this->errorMessage = null;

        $error = $this->deletableError($assessmentId, $assessmentService);

        if ($error) {
            $this->errorMessage = $error;

            return;
        }

        $assessmentService->delete($assessmentId);
    }

    /**
     * @param  array<int, string>  $assessmentIds
     */
    public function deleteSelected(array $assessmentIds, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.delete'), 403);

        $this->errorMessage = null;

        if (empty($assessmentIds)) {
            return;
        }

        $skipped = 0;

        foreach ($assessmentIds as $assessmentId) {
            if ($this->deletableError($assessmentId, $assessmentService)) {
                $skipped++;

                continue;
            }

            $assessmentService->delete($assessmentId);
        }

        if ($skipped > 0) {
            $this->errorMessage = __('Some selected assessments could not be deleted because they are auto-provisioned or already have submissions.');
        }
    }

    public function moveAssessment(string $assessmentId, string $direction, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.edit'), 403);

        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $assessmentService->moveOrder($assessmentId, $direction);
    }

    /**
     * Persists the drag-and-drop reordering of a type group's assessments.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function reorderAssessments(string $type, array $orderedIds, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.edit'), 403);

        $assessmentService->reorder($this->course->id, $type, $orderedIds);
    }

    private function deletableError(string $assessmentId, AssessmentService $assessmentService): ?string
    {
        $assessment = $assessmentService->find($assessmentId, ['attempts']);

        if (! $assessment || $assessment->course_id !== $this->course->id) {
            return __('Assessment not found.');
        }

        if ($assessment->type === AssessmentType::Attendance) {
            return __('The Attendance assessment is auto-provisioned and cannot be deleted.');
        }

        if ($assessment->type === AssessmentType::ForumDiscussion) {
            return __('The Forum Discussion assessment is auto-provisioned and cannot be deleted.');
        }

        if ($assessment->attempts->isNotEmpty()) {
            return __('This assessment already has submissions and cannot be deleted.');
        }

        return null;
    }

    /**
     * Dev-only: bulk-creates draft personal assignments, each seeded with
     * the same number of questions as devAutofill (see AssessmentForm) so
     * generated rows are realistic, but with its own wording so the two
     * dev tools don't produce identical-looking content.
     */
    public function generatePersonalAssignments(AssessmentService $assessmentService, AssessmentQuestionService $assessmentQuestionService, MediaLibraryService $mediaLibraryService, R2StorageService $r2StorageService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('assessment.create'), 403);

        $this->errorMessage = null;

        $this->validate([
            'generateCount' => 'required|integer|min:1|max:50',
        ]);

        $count = (int) $this->generateCount;

        $questionContent = [
            'Summarize the key takeaway from this week\'s reading and explain why it matters for the course topic.',
            'Identify a potential limitation of the method covered this week and propose how it could be addressed.',
            'Walk through how you would apply this week\'s technique to a problem outside the examples shown in class.',
        ];

        $materialIds = $this->devMaterialIds($this->course->school_id, $mediaLibraryService, $r2StorageService);

        for ($i = 0; $i < $count; $i++) {
            $startDate = now()->addWeeks($i);
            $endDate = $startDate->clone()->addWeek();

            $assessment = $assessmentService->create([
                'course_id' => $this->course->id,
                'session_id' => null,
                'type' => AssessmentType::TheoryPersonalAssignment,
                'title' => AssessmentTypeLabel::forType(AssessmentType::TheoryPersonalAssignment).' - Week '.random_int(1, 14).' Practice',
                'weight' => AssessmentType::TheoryPersonalAssignment->defaultWeight(),
                'assigned_to' => AssessmentAssignedTo::Individual,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => AssessmentStatus::Draft,
            ]);

            foreach ($questionContent as $index => $description) {
                $question = $assessmentQuestionService->create([
                    'assessment_id' => $assessment->id,
                    'description' => '<p>'.$description.'</p>',
                    'points' => ($index + 1) * 10,
                    'order' => $index + 1,
                ]);

                $materialSync = [];
                foreach (array_values($materialIds) as $order => $materialId) {
                    $materialSync[$materialId] = ['order' => $order + 1];
                }
                $question->files()->sync($materialSync);
            }
        }
    }

    public function render(AssessmentService $assessmentService, AssessmentAttemptService $assessmentAttemptService, CoursePersonService $coursePersonService, GroupMemberService $groupMemberService, QuizAttemptScoringService $quizAttemptScoringService, AttendanceScoringService $attendanceScoringService, AttendanceDerivationService $attendanceDerivationService, ForumDiscussionScoringService $forumDiscussionScoringService, ProctorSessionService $proctorSessionService)
    {
        if (! $this->assessmentsLoaded) {
            return view('livewire.courses.assessment-index-placeholder', [
                'course' => $this->course,
                'courseTabs' => CourseTabs::build($this->course, 'assessment'),
                'teacher' => $this->isStudent
                    ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                    : null,
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $assessments = $assessmentService->get(['course_id' => $this->course->id], ['attempts.score']);

        $rows = $assessments->mapWithKeys(function (Assessment $assessment) use ($assessmentAttemptService, $groupMemberService, $quizAttemptScoringService, $attendanceScoringService, $forumDiscussionScoringService, $proctorSessionService) {
            return [$assessment->id => $this->rowStatus($assessment, $assessmentAttemptService, $groupMemberService, $quizAttemptScoringService, $attendanceScoringService, $forumDiscussionScoringService, $proctorSessionService)];
        })->all();

        $allSessions = $attendanceDerivationService->sessionsForCourse($this->course);

        $virtualClassSessions = $allSessions
            ->filter(fn (Session $session) => $session->delivery_mode === DeliveryMode::VirtualClass)
            ->values();

        $onlineSessions = $allSessions
            ->filter(fn (Session $session) => $session->delivery_mode === DeliveryMode::Online)
            ->values();

        $sessionPositions = $allSessions->values()
            ->mapWithKeys(fn (Session $session, int $index) => [$session->id => $index + 1])
            ->all();

        $grouped = collect(AssessmentType::cases())
            ->map(fn (AssessmentType $type) => $this->buildTypeGroup($type, $assessments, $rows, $attendanceDerivationService, $virtualClassSessions, $onlineSessions, $forumDiscussionScoringService, $sessionPositions))
            ->all();

        return view('livewire.courses.assessment-index', [
            'course' => $this->course,
            'isStudent' => $this->isStudent,
            'groupedAssessments' => $grouped,
            'rowStatus' => $rows,
            'courseTabs' => CourseTabs::build($this->course, 'assessment'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * @param  Collection<int, Assessment>  $assessments
     * @param  array<string, array{status: string, route: string|null, attemptCount: int, attemptLimit: string, score: float|null, isExpired: bool, statusConfig: array{bg: string, text: string, icon: string}}>  $rows
     * @param  Collection<int, Session>  $virtualClassSessions
     * @param  Collection<int, Session>  $onlineSessions
     * @param  array<string, int>  $sessionPositions
     * @return array<string, mixed>
     */
    private function buildTypeGroup(AssessmentType $type, Collection $assessments, array $rows, AttendanceDerivationService $attendanceDerivationService, Collection $virtualClassSessions, Collection $onlineSessions, ForumDiscussionScoringService $forumDiscussionScoringService, array $sessionPositions): array
    {
        $group = [
            'type' => $type,
            'assessments' => $assessments->where('type', $type)->values()->map(fn (Assessment $a) => [
                'data' => $a,
                'row' => $rows[$a->id],
                'sessionPosition' => $a->session_id ? ($sessionPositions[$a->session_id] ?? null) : null,
                'isReorderable' => ! $this->isStudent && ! in_array($type, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true),
            ]),
            'sectionKey' => $type->value,
            'isExpanded' => isset($this->expandedSections[$type->value]) && $this->expandedSections[$type->value],
            'totalWeight' => $assessments->where('type', $type)->sum('weight'),
        ];

        if ($type === AssessmentType::Attendance && $this->isStudent) {
            $group['sessionRows'] = $virtualClassSessions->map(fn (Session $session) => [
                'session' => $session,
                'attended' => $attendanceDerivationService->isSessionAttended($session, auth()->id()),
            ]);
        }

        if ($type === AssessmentType::ForumDiscussion && $this->isStudent) {
            $group['sessionRows'] = $onlineSessions->map(fn (Session $session) => [
                'session' => $session,
                'met' => $forumDiscussionScoringService->hasMetForumPostRequirement($session, auth()->id()),
                'required' => $forumDiscussionScoringService->requiredForumPosts($session),
            ]);
        }

        return $group;
    }

    public function editRoute(Assessment $assessment): string
    {
        return match ($assessment->type) {
            AssessmentType::TheoryQuiz => route('assessments.quiz.edit', $assessment),
            AssessmentType::TheoryFinalExam => route('assessments.final-exam.edit', $assessment),
            default => route('assessments.edit', $assessment),
        };
    }

    /**
     * @return array{status: string, route: string|null, attemptCount: int, attemptLimit: string, score: float|null, isExpired: bool, statusConfig: array{bg: string, text: string, icon: string}}
     */
    private function rowStatus(Assessment $assessment, AssessmentAttemptService $assessmentAttemptService, GroupMemberService $groupMemberService, QuizAttemptScoringService $quizAttemptScoringService, AttendanceScoringService $attendanceScoringService, ForumDiscussionScoringService $forumDiscussionScoringService, ProctorSessionService $proctorSessionService): array
    {
        $type = $assessment->type;
        $isExpired = $assessment->end_date && $assessment->end_date->isPast();

        $attemptLimit = $type === AssessmentType::TheoryQuiz
            ? $assessment->quiz?->total_attempts
            : $assessment->attempt_limit;
        $attemptLimit = $attemptLimit ? (string) $attemptLimit : 'unlimited';

        $route = match ($type) {
            AssessmentType::TheoryPersonalAssignment => route('assessments.personal.show', $assessment),
            AssessmentType::TheoryTeamAssignment => route('assessments.team.show', $assessment),
            AssessmentType::TheoryQuiz => route('assessments.quiz.show', $assessment),
            AssessmentType::TheoryFinalExam => route('assessments.final-exam.show', $assessment),
            AssessmentType::Attendance => route('assessments.attendance.show', $assessment),
            AssessmentType::ForumDiscussion => route('assessments.forum-discussion.show', $assessment),
        };

        $base = [
            'route' => $route,
            'attemptCount' => 0,
            'attemptLimit' => $attemptLimit,
            'score' => null,
            'isExpired' => $isExpired,
        ];

        if (! $this->isStudent) {
            return [
                ...$base,
                'status' => $assessment->status->value,
                'statusConfig' => $this->statusConfig($assessment->status->value),
            ];
        }

        if ($type === AssessmentType::TheoryQuiz) {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($assessment->id, auth()->id())
                ->filter(fn ($attempt) => $attempt->submitted_at !== null)
                ->values();

            if ($attempts->isEmpty()) {
                return [
                    ...$base,
                    'status' => 'not_started',
                    'feedback' => null,
                    'statusConfig' => $this->statusConfig('not_started'),
                ];
            }

            $scoredAttempt = $attempts->first(fn ($attempt) => $attempt->score !== null);
            $pending = $attempts->contains(fn ($attempt) => $quizAttemptScoringService->hasPendingGrading($attempt->id));
            $status = $pending ? 'submitted' : 'graded';

            return [
                ...$base,
                'status' => $status,
                'attemptCount' => $attempts->count(),
                'score' => $scoredAttempt?->score?->score,
                'statusConfig' => $this->statusConfig($status),
            ];
        }

        if ($type === AssessmentType::Attendance) {
            $computed = $attendanceScoringService->computeForUser($assessment, auth()->id());

            return [
                ...$base,
                'status' => 'graded',
                'score' => $computed['score'],
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        if ($type === AssessmentType::ForumDiscussion) {
            $computed = $forumDiscussionScoringService->computeForUser($assessment, auth()->id());

            return [
                ...$base,
                'status' => 'graded',
                'score' => $computed['score'],
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        if ($type === AssessmentType::TheoryTeamAssignment) {
            $member = $groupMemberService->get(['user_id' => auth()->id()])
                ->first(fn (GroupMember $m) => $m->group->course_id === $this->course->id);
            $attempts = $member ? $assessmentAttemptService->forAssessmentAndGroup($assessment->id, $member->group_id) : collect();
        } else {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($assessment->id, auth()->id());
        }

        $latest = $attempts->last();
        $score = $latest?->score?->score;
        $base['attemptCount'] = $attempts->count();

        if (! $latest) {
            return [
                ...$base,
                'status' => 'not_started',
                'attemptCount' => 0,
                'statusConfig' => $this->statusConfig('not_started'),
            ];
        }

        if ($latest->submitted_at === null) {
            return [
                ...$base,
                'status' => 'in_progress',
                'feedback' => null,
                'statusConfig' => $this->statusConfig('in_progress'),
            ];
        }

        if ($score !== null) {
            $isProctoredFinalExam = $type === AssessmentType::TheoryFinalExam
                && in_array($assessment->finalExam?->exam_type?->value, ['open_book', 'closed_book'], true);

            if ($isProctoredFinalExam) {
                $proctorSession = $proctorSessionService->findByAttempt($latest->id);

                if ($proctorSession !== null && $proctorSession->reviewed_at === null) {
                    return [
                        ...$base,
                        'status' => 'pending_review',
                        'score' => null,
                        'feedback' => null,
                        'statusConfig' => $this->statusConfig('pending_review'),
                    ];
                }

                if ($proctorSession?->review_decision === ProctorReviewDecision::Disqualified) {
                    return [
                        ...$base,
                        'status' => 'disqualified',
                        'score' => $score,
                        'statusConfig' => $this->statusConfig('disqualified'),
                    ];
                }
            }

            return [
                ...$base,
                'status' => 'graded',
                'score' => $score,
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        return [
            ...$base,
            'status' => 'submitted',
            'feedback' => null,
            'statusConfig' => $this->statusConfig('submitted'),
        ];
    }

    /**
     * @return array{bg: string, text: string, icon: string}
     */
    private function statusConfig(string $status): array
    {
        return match ($status) {
            'completed', 'graded' => ['bg' => 'bg-success/10', 'text' => 'text-success', 'icon' => 'check_circle'],
            'submitted', 'pending_review' => ['bg' => 'bg-warning/10', 'text' => 'text-warning', 'icon' => 'schedule'],
            'not_started' => ['bg' => 'bg-on-surface-variant/10', 'text' => 'text-on-surface-variant', 'icon' => 'pending'],
            'in_progress' => ['bg' => 'bg-primary/10', 'text' => 'text-primary', 'icon' => 'timelapse'],
            'disqualified' => ['bg' => 'bg-error/10', 'text' => 'text-error', 'icon' => 'cancel'],
            default => ['bg' => 'bg-on-surface-variant/10', 'text' => 'text-on-surface-variant', 'icon' => 'help'],
        };
    }
}
