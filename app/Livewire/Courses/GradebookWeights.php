<?php

namespace App\Livewire\Courses;

use App\Enums\AssessmentType;
use App\Livewire\Courses\Concerns\BuildsGradebookViewData;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Session;
use App\Services\AssessmentService;
use App\Services\AttendanceScoringService;
use App\Services\ForumDiscussionScoringService;
use App\Services\GradebookScoringService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Support\Collection;
use Livewire\Component;

class GradebookWeights extends Component
{
    use BuildsGradebookViewData;

    public Course $course;

    public bool $dataLoaded = false;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('gradebook.manage') && $course->school_id === $schoolId, 403);

        $this->course = $course;
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    public function clearSuccessMessage(): void
    {
        $this->successMessage = null;
    }

    /**
     * @param  array<string, mixed>  $weights  AssessmentType value => new total weight, validated client-side before this is ever called
     */
    public function save(array $weights, GradebookScoringService $gradebookScoringService): void
    {
        abort_unless(auth()->user()->can('gradebook.manage'), 403);

        $weights = collect($weights)->map(fn ($weight) => (float) $weight);

        foreach ($weights as $weight) {
            if ($weight < 0 || $weight > 100) {
                $this->errorMessage = __('Every weight must be between 0 and 100.');

                return;
            }
        }

        if (round($weights->sum(), 2) !== 100.0) {
            $this->errorMessage = __('Weights must add up to 100%.');

            return;
        }

        $gradebookScoringService->updateTypeWeights($this->course, $weights->all());

        $this->errorMessage = null;
        $this->successMessage = __('Weights updated and scores recalculated for every student.');
    }

    public function render(
        AssessmentService $assessmentService,
        AttendanceScoringService $attendanceScoringService,
        ForumDiscussionScoringService $forumDiscussionScoringService,
    ) {
        $viewData = [
            'course' => $this->course,
            'courseTabs' => CourseTabs::build($this->course, 'gradebook'),
        ];

        if (! $this->dataLoaded) {
            return view('livewire.courses.gradebook-weights-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $assessmentsByType = $assessmentService->forCourse($this->course->id)->groupBy(
            fn ($assessment) => $assessment->type->value
        );

        $viewData['typeRows'] = collect(AssessmentType::cases())
            ->filter(fn (AssessmentType $type) => $assessmentsByType->has($type->value))
            ->map(function (AssessmentType $type) use ($assessmentsByType, $attendanceScoringService, $forumDiscussionScoringService) {
                $typeAssessments = $assessmentsByType->get($type->value);

                return [
                    'key' => $type->value,
                    'label' => $this->typeLabel($type->value),
                    'weight' => (float) $typeAssessments->sum('weight'),
                    'items' => $this->itemsForType($type, $typeAssessments, $attendanceScoringService, $forumDiscussionScoringService),
                ];
            })
            ->values();

        return view('livewire.courses.gradebook-weights', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * For Attendance/Forum Discussion (session-based, auto-provisioned as a
     * single Assessment), lists that Assessment's in-scope Sessions instead
     * of the Assessment itself, matching AssessmentIndex's session listing.
     *
     * @param  Collection<int, Assessment>  $typeAssessments
     * @return Collection<int, non-falsy-string>
     */
    private function itemsForType(
        AssessmentType $type,
        Collection $typeAssessments,
        AttendanceScoringService $attendanceScoringService,
        ForumDiscussionScoringService $forumDiscussionScoringService,
    ): Collection {
        if (! in_array($type, [AssessmentType::Attendance, AssessmentType::ForumDiscussion], true)) {
            return $typeAssessments->pluck('title')->values();
        }

        $sessions = $typeAssessments->flatMap(
            fn (Assessment $assessment) => $type === AssessmentType::Attendance
                ? $attendanceScoringService->sessionsInScope($assessment)
                : $forumDiscussionScoringService->sessionsInScope($assessment)
        )->unique('id')->values();

        return $sessions->map(
            function (Session $session): string {
                return 'Session '.$session->order.' - '.str($session->delivery_mode->value)->replace('_', ' ')->title();
            }
        )->values();
    }
}
