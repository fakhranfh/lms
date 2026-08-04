<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\CourseService;
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

    public function mount(): void
    {
        abort_unless(auth()->user()->can('courses.view'), 403);
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

    public function render(CurrentSchool $currentSchool)
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        $query = Course::where('school_id', $schoolId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereLike('title', "%{$this->search}%", caseSensitive: false)
                    ->orWhereLike('description', "%{$this->search}%", caseSensitive: false);
            });
        }

        $courses = $query->with(['creator', 'sessions'])->latest()->paginate(10);

        return view('livewire.courses.courses-index', [
            'courses' => $courses,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Courses'])
            ->section('app-content');
    }
}
