<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class CourseComingSoon extends Component
{
    public Course $course;

    public string $tab;

    /** @var array<string, string> */
    public array $tabLabels = [
        'forum' => 'Forum',
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
    }

    public function render()
    {
        return view('livewire.courses.course-coming-soon', [
            'tabLabel' => $this->tabLabels[$this->tab],
            'courseTabs' => CourseTabs::build($this->course, $this->tab),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
