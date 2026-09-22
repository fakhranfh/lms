<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Livewire\Courses\Concerns\HasCoursesIndexDevTools;
use App\Services\CourseService;
use App\Services\SessionMaterialCompletionService;
use App\Support\CurrentSchool;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CoursesIndex extends Component
{
    use HasCoursesIndexDevTools, WithPagination;

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
        $this->dispatch('courses-selection-cleared');
    }

    /**
     * @param  array<int, string>  $ids
     */
    #[On('bulk-delete-confirmed')]
    public function bulkDestroy(array $ids, CourseService $courseService): void
    {
        abort_unless(auth()->user()->can('courses.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        if (empty($ids)) {
            return;
        }

        $count = $courseService->bulkDelete($ids);

        $this->successMessage = trans_choice('{1} :count course deleted successfully.|[2,*] :count courses deleted successfully.', $count, ['count' => $count]);
        $this->resetPage();
        $this->dispatch('courses-selection-cleared');
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
            $filters['enrolled_user_id'] = auth()->id();
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
            'canGenerateCourses' => ! $this->isStudent && auth()->user()->can('courses.create') && app()->environment(['local', 'testing']),
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Courses'])
            ->section('app-content');
    }
}
