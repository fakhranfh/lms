<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Services\CourseService;
use App\Services\SessionMaterialCompletionService;
use App\Support\CurrentSchool;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CoursesIndex extends Component
{
    use WithPagination;

    public ?string $search = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $isStudent = false;

    /**
     * Courses are queried lazily via wire:init (loadCourses), so the initial
     * page render is a cheap skeleton instead of blocking on the query.
     */
    public bool $coursesLoaded = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('courses.view'), 403);

        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function loadCourses(): void
    {
        $this->coursesLoaded = true;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('delete-confirmed')]
    public function destroy(string $id, CourseService $courseService): void
    {
        abort_unless(auth()->user()->can('courses.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $courseService->find($id)) {
            $this->errorMessage = __('Course not found.');

            return;
        }

        $courseService->delete($id);
        $this->successMessage = __('Course deleted successfully.');
        $this->resetPage();
    }

    public function render(CurrentSchool $currentSchool, CourseService $courseService, SessionMaterialCompletionService $completionService)
    {
        if (! $this->coursesLoaded) {
            return view('livewire.courses.courses-index-placeholder', [
                'isStudent' => $this->isStudent,
            ])
                ->extends('layouts.app', ['topbarTitle' => 'Courses'])
                ->section('app-content');
        }

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

        $filters = ['school_id' => $schoolId, 'search' => $this->search];

        if ($this->isStudent) {
            $filters['is_published'] = true;
        }

        $courses = $courseService->paginate($filters, ['creator', 'sessions'], 10);

        $courseProgress = $this->isStudent
            ? collect($courses->items())->mapWithKeys(
                fn ($course) => [$course->id => $completionService->courseProgressPercent($course->id, auth()->id())]
            )
            : collect();

        return view('livewire.courses.courses-index', [
            'courses' => $courses,
            'isStudent' => $this->isStudent,
            'courseProgress' => $courseProgress,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Courses'])
            ->section('app-content');
    }
}
