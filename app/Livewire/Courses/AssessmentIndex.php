<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Enums\RoleName;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\GroupMember;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentService;
use App\Services\CoursePersonService;
use App\Services\GroupMemberService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class AssessmentIndex extends Component
{
    public Course $course;

    public bool $isStudent = false;

    public bool $assessmentsLoaded = false;

    public ?string $errorMessage = null;

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

    public function deleteAssessment(string $assessmentId, AssessmentService $assessmentService): void
    {
        abort_unless(auth()->user()->can('assessment.delete'), 403);

        $assessment = $assessmentService->find($assessmentId, ['attempts']);

        if (! $assessment || $assessment->course_id !== $this->course->id) {
            $this->errorMessage = __('Assessment not found.');

            return;
        }

        if ($assessment->attempts->isNotEmpty()) {
            $this->errorMessage = __('This assessment already has submissions and cannot be deleted.');

            return;
        }

        $assessmentService->delete($assessmentId);
    }

    public function render(AssessmentService $assessmentService, AssessmentAttemptService $assessmentAttemptService, CoursePersonService $coursePersonService, GroupMemberService $groupMemberService)
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

        $grouped = collect(AssessmentType::cases())
            ->map(fn (AssessmentType $type) => [
                'type' => $type,
                'assessments' => $assessments->where('type', $type)->values(),
            ])
            ->all();

        $rows = $assessments->mapWithKeys(function (Assessment $assessment) use ($assessmentAttemptService, $groupMemberService) {
            return [$assessment->id => $this->rowStatus($assessment, $assessmentAttemptService, $groupMemberService)];
        })->all();

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
     * @return array{status: string, route: string|null}
     */
    private function rowStatus(Assessment $assessment, AssessmentAttemptService $assessmentAttemptService, GroupMemberService $groupMemberService): array
    {
        $type = $assessment->type;

        $route = match ($type) {
            AssessmentType::TheoryPersonalAssignment => route('assessments.personal.show', $assessment),
            AssessmentType::TheoryTeamAssignment => route('assessments.team.show', $assessment),
            default => null,
        };

        if (! $this->isStudent) {
            return ['status' => $assessment->status->value, 'route' => $route];
        }

        if ($type === AssessmentType::TheoryPersonalAssignment) {
            $attempts = $assessmentAttemptService->forAssessmentAndUser($assessment->id, auth()->id());
        } elseif ($type === AssessmentType::TheoryTeamAssignment) {
            $member = $groupMemberService->get(['user_id' => auth()->id()])
                ->first(fn (GroupMember $m) => $m->group->course_id === $this->course->id);
            $attempts = $member ? $assessmentAttemptService->forAssessmentAndGroup($assessment->id, $member->group_id) : collect();
        } else {
            return ['status' => 'unavailable', 'route' => null];
        }

        $latest = $attempts->last();

        if (! $latest) {
            return ['status' => 'not_started', 'route' => $route];
        }

        if ($latest->score) {
            return ['status' => 'graded', 'route' => $route];
        }

        return ['status' => 'submitted', 'route' => $route];
    }
}
