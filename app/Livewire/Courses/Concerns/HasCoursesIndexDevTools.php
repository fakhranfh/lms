<?php

namespace App\Livewire\Courses\Concerns;

use App\Services\DemoCourseGeneratorService;
use App\Support\CurrentSchool;

trait HasCoursesIndexDevTools
{
    public int $generateCount = 1;

    /**
     * Dev-only: bulk-generates full demo courses (sessions, syllabus,
     * materials, every assessment type, students, and groups) so the UI can
     * be exercised without manually building content course by course.
     */
    public function devGenerateCourses(DemoCourseGeneratorService $generator, CurrentSchool $currentSchool): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('courses.create'), 403);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        $count = max(1, min(20, $this->generateCount));

        $courses = $generator->generate($schoolId, auth()->id(), $count);

        $this->successMessage = __(':count course(s) generated.', ['count' => $courses->count()]);
        $this->resetPage();
    }
}
