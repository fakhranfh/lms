<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\GroupMember;
use App\Models\Session;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentService;
use App\Services\AttendanceDerivationService;
use App\Services\AttendanceScoringService;
use App\Services\CoursePersonService;
use App\Services\ForumDiscussionScoringService;
use App\Services\GroupMemberService;
use App\Services\QuizAttemptScoringService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Support\Collection;
use Livewire\Component;

class AssessmentIndex extends Component
{
    public Course $course;

    public bool $isStudent = false;

    public bool $assessmentsLoaded = false;

    public ?string $errorMessage = null;

    public array $expandedSections = [];

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

        $assessment = $assessmentService->find($assessmentId, ['attempts']);

        if (! $assessment || $assessment->course_id !== $this->course->id) {
            $this->errorMessage = __('Assessment not found.');

            return;
        }

        if ($assessment->type === AssessmentType::Attendance) {
            $this->errorMessage = __('The Attendance assessment is auto-provisioned and cannot be deleted.');

            return;
        }

        if ($assessment->type === AssessmentType::ForumDiscussion) {
            $this->errorMessage = __('The Forum Discussion assessment is auto-provisioned and cannot be deleted.');

            return;
        }

        if ($assessment->attempts->isNotEmpty()) {
            $this->errorMessage = __('This assessment already has submissions and cannot be deleted.');

            return;
        }

        $assessmentService->delete($assessmentId);
    }

    public function render(AssessmentService $assessmentService, AssessmentAttemptService $assessmentAttemptService, CoursePersonService $coursePersonService, GroupMemberService $groupMemberService, QuizAttemptScoringService $quizAttemptScoringService, AttendanceScoringService $attendanceScoringService, AttendanceDerivationService $attendanceDerivationService, ForumDiscussionScoringService $forumDiscussionScoringService)
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

        $rows = $assessments->mapWithKeys(function (Assessment $assessment) use ($assessmentAttemptService, $groupMemberService, $quizAttemptScoringService, $attendanceScoringService, $forumDiscussionScoringService) {
            return [$assessment->id => $this->rowStatus($assessment, $assessmentAttemptService, $groupMemberService, $quizAttemptScoringService, $attendanceScoringService, $forumDiscussionScoringService)];
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

    /**
     * @return array{status: string, route: string|null, attemptCount: int, attemptLimit: string, score: float|null, isExpired: bool, statusConfig: array{bg: string, text: string, icon: string}}
     */
    private function rowStatus(Assessment $assessment, AssessmentAttemptService $assessmentAttemptService, GroupMemberService $groupMemberService, QuizAttemptScoringService $quizAttemptScoringService, AttendanceScoringService $attendanceScoringService, ForumDiscussionScoringService $forumDiscussionScoringService): array
    {
        $type = $assessment->type;
        $isExpired = $assessment->end_date && $assessment->end_date->isPast();

        $route = match ($type) {
            AssessmentType::TheoryPersonalAssignment => route('assessments.personal.show', $assessment),
            AssessmentType::TheoryTeamAssignment => route('assessments.team.show', $assessment),
            AssessmentType::TheoryQuiz => route('assessments.quiz.show', $assessment),
            AssessmentType::Attendance => route('assessments.attendance.show', $assessment),
            AssessmentType::ForumDiscussion => route('assessments.forum-discussion.show', $assessment),
            default => null,
        };

        if (! $this->isStudent) {
            return [
                'status' => $assessment->status->value,
                'route' => $route,
                'attemptCount' => 0,
                'attemptLimit' => 'unlimited',
                'score' => null,
                'isExpired' => $isExpired,
                'statusConfig' => $this->statusConfig($assessment->status->value),
            ];
        }

        if ($type === AssessmentType::TheoryQuiz) {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($assessment->id, auth()->id())
                ->filter(fn ($attempt) => $attempt->submitted_at !== null)
                ->values();

            if ($attempts->isEmpty()) {
                return [
                    'status' => 'not_started',
                    'route' => $route,
                    'attemptCount' => 0,
                    'attemptLimit' => 'unlimited',
                    'score' => null,
                    'isExpired' => $isExpired,
                    'statusConfig' => $this->statusConfig('not_started'),
                ];
            }

            $scoredAttempt = $attempts->first(fn ($attempt) => $attempt->score !== null);
            $pending = $attempts->contains(fn ($attempt) => $quizAttemptScoringService->hasPendingGrading($attempt->id));

            return [
                'status' => $pending ? 'submitted' : 'graded',
                'route' => $route,
                'attemptCount' => $attempts->count(),
                'attemptLimit' => 'unlimited',
                'score' => $scoredAttempt?->score?->score,
                'isExpired' => $isExpired,
                'statusConfig' => $this->statusConfig($pending ? 'submitted' : 'graded'),
            ];
        }

        if ($type === AssessmentType::Attendance) {
            $computed = $attendanceScoringService->computeForUser($assessment, auth()->id());

            return [
                'status' => 'graded',
                'route' => $route,
                'attemptCount' => 0,
                'attemptLimit' => 'unlimited',
                'score' => $computed['score'],
                'isExpired' => $isExpired,
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        if ($type === AssessmentType::ForumDiscussion) {
            $computed = $forumDiscussionScoringService->computeForUser($assessment, auth()->id());

            return [
                'status' => 'graded',
                'route' => $route,
                'attemptCount' => 0,
                'attemptLimit' => 'unlimited',
                'score' => $computed['score'],
                'isExpired' => $isExpired,
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        if ($type === AssessmentType::TheoryPersonalAssignment) {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($assessment->id, auth()->id());
        } elseif ($type === AssessmentType::TheoryTeamAssignment) {
            $member = $groupMemberService->get(['user_id' => auth()->id()])
                ->first(fn (GroupMember $m) => $m->group->course_id === $this->course->id);
            $attempts = $member ? $assessmentAttemptService->forAssessmentAndGroup($assessment->id, $member->group_id) : collect();
        } else {
            return [
                'status' => 'unavailable',
                'route' => null,
                'attemptCount' => 0,
                'attemptLimit' => 'unlimited',
                'score' => null,
                'isExpired' => $isExpired,
                'statusConfig' => $this->statusConfig('unavailable'),
            ];
        }

        $latest = $attempts->last();
        $score = $latest?->score?->score;

        if (! $latest) {
            return [
                'status' => 'not_started',
                'route' => $route,
                'attemptCount' => 0,
                'attemptLimit' => 'unlimited',
                'score' => null,
                'isExpired' => $isExpired,
                'statusConfig' => $this->statusConfig('not_started'),
            ];
        }

        if ($score) {
            return [
                'status' => 'graded',
                'route' => $route,
                'attemptCount' => $attempts->count(),
                'attemptLimit' => 'unlimited',
                'score' => $score,
                'isExpired' => $isExpired,
                'statusConfig' => $this->statusConfig('graded'),
            ];
        }

        return [
            'status' => 'submitted',
            'route' => $route,
            'attemptCount' => $attempts->count(),
            'attemptLimit' => 'unlimited',
            'score' => null,
            'isExpired' => $isExpired,
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
            'submitted' => ['bg' => 'bg-warning/10', 'text' => 'text-warning', 'icon' => 'schedule'],
            'not_started' => ['bg' => 'bg-on-surface-variant/10', 'text' => 'text-on-surface-variant', 'icon' => 'pending'],
            default => ['bg' => 'bg-on-surface-variant/10', 'text' => 'text-on-surface-variant', 'icon' => 'help'],
        };
    }
}
