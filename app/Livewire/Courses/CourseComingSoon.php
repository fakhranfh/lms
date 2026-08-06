<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Models\Course;
use App\Services\CoursePersonService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class CourseComingSoon extends Component
{
    public Course $course;

    public string $tab;

    public bool $isStudent = false;

    /** @var array<string, string> */
    public array $tabLabels = [
        'assessment' => 'Assessment',
        'gradebook' => 'Gradebook',
        'people' => 'People',
        'attendance' => 'Attendance',
    ];

    public function mount(CurrentSchool $currentSchool, Course $course, string $tab): void
    {
        abort_unless(auth()->user()->can('courses.view'), 403);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($course->school_id === $schoolId, 403);

        abort_unless(array_key_exists($tab, $this->tabLabels), 404);

        $this->course = $course;
        $this->tab = $tab;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function render(CoursePersonService $coursePersonService)
    {
        return view('livewire.courses.course-coming-soon', [
            'tabLabel' => $this->tabLabels[$this->tab],
            'courseTabs' => CourseTabs::build($this->course, $this->tab),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
