<?php

namespace App\Livewire\Courses;

use App\Livewire\Courses\Concerns\BuildsGradebookViewData;
use App\Models\Course;
use App\Models\User;
use App\Services\CoursePersonService;
use App\Services\GradebookScoringService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class GradebookShow extends Component
{
    use BuildsGradebookViewData;

    public Course $course;

    public User $student;

    public bool $dataLoaded = false;

    public function mount(CurrentSchool $currentSchool, Course $course, User $student, CoursePersonService $coursePersonService): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('gradebook.view') && $course->school_id === $schoolId, 403);

        $isSelf = $student->id === auth()->id();
        abort_unless($isSelf || auth()->user()->can('gradebook.manage'), 403);
        abort_unless($coursePersonService->isEnrolledAsStudent($course->id, $student->id), 404);

        $this->course = $course;
        $this->student = $student;
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    public function render(GradebookScoringService $gradebookScoringService)
    {
        $viewData = [
            'course' => $this->course,
            'student' => $this->student,
            'courseTabs' => CourseTabs::build($this->course, 'gradebook'),
        ];

        if (! $this->dataLoaded) {
            return view('livewire.courses.gradebook-show-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $result = $gradebookScoringService->computeForUser($this->course, $this->student->id);

        $viewData['result'] = $result;
        $viewData['finalGrade'] = $this->letterGrade($result['final']['score']);
        $viewData['finalLastUpdatedLabel'] = $this->lastUpdatedLabel($result['final']['last_updated_at']);
        $viewData['typeRows'] = $this->typeRows($result, $this->student->id);

        return view('livewire.courses.gradebook-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
