<?php

namespace App\Livewire\Raport;

use App\Livewire\Raport\Concerns\BuildsRaportViewData;
use App\Models\Course;
use App\Models\User;
use App\Services\CoursePersonService;
use App\Services\CourseService;
use App\Services\GradebookScoringService;
use App\Support\CurrentSchool;
use Livewire\Component;

class RaportShow extends Component
{
    use BuildsRaportViewData;

    public User $student;

    public bool $isSelf = false;

    public bool $dataLoaded = false;

    public function mount(CurrentSchool $currentSchool, User $student, CourseService $courseService, CoursePersonService $coursePersonService): void
    {
        abort_unless(auth()->user()->can('raport.view'), 403);

        $this->isSelf = $student->id === auth()->id();

        if (! $this->isSelf) {
            $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

            $teachesStudent = $courseService->get(['teaching_user_id' => auth()->id(), 'school_id' => $schoolId])
                ->contains(fn (Course $course) => $coursePersonService->isEnrolledAsStudent($course->id, $student->id));

            abort_unless($teachesStudent, 403);
        }

        $this->student = $student;
    }

    public function loadData(): void
    {
        $this->dataLoaded = true;
    }

    public function render(
        CurrentSchool $currentSchool,
        CourseService $courseService,
        CoursePersonService $coursePersonService,
        GradebookScoringService $gradebookScoringService,
    ) {
        $viewData = [
            'student' => $this->student,
            'isSelf' => $this->isSelf,
        ];

        if (! $this->dataLoaded) {
            return view('livewire.raport.raport-show-placeholder', $viewData)
                ->extends('layouts.app', ['topbarTitle' => $this->student->name])
                ->section('app-content');
        }

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

        $courses = $this->isSelf
            ? $courseService->get(['enrolled_user_id' => $this->student->id, 'school_id' => $schoolId])
            : $courseService->get(['teaching_user_id' => auth()->id(), 'school_id' => $schoolId])
                ->filter(fn (Course $course) => $coursePersonService->isEnrolledAsStudent($course->id, $this->student->id))
                ->values();

        $viewData['courseCards'] = $courses->map(function (Course $course) use ($gradebookScoringService) {
            $result = $gradebookScoringService->computeForUser($course, $this->student->id);

            return [
                'course' => $course,
                'finalScore' => $result['final']['score'],
                'finalGrade' => $this->letterGrade($course, $result['final']['score']),
                'finalLastUpdatedLabel' => $this->lastUpdatedLabel($result['final']['last_updated_at']),
                'typeRows' => $this->typeRows($result),
            ];
        })->values();

        $viewData['overallScore'] = $this->overallScore($viewData['courseCards']);
        $viewData['overallGrade'] = $this->overallGrade($viewData['overallScore']);

        return view('livewire.raport.raport-show', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->student->name])
            ->section('app-content');
    }
}
