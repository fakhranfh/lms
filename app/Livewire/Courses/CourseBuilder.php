<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\LessonService;
use App\Services\ModuleService;
use App\Support\CurrentSchool;
use Livewire\Attributes\On;
use Livewire\Component;

class CourseBuilder extends Component
{
    public Course $course;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $isStudent = false;

    /** @var array<string, bool> */
    public array $expandedModules = [];

    public function mount(Course $course, CurrentSchool $currentSchool): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('courses.view') && $course->school_id === $schoolId, 403);
        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole('Student');
    }

    #[On('module-created')]
    #[On('module-updated')]
    #[On('lesson-created')]
    #[On('lesson-updated')]
    public function refreshCourse(): void
    {
        $this->course->refresh();
        $this->successMessage = __('Successfully updated.');
    }

    public function toggleModule(string $moduleId): void
    {
        $this->expandedModules[$moduleId] = ! ($this->expandedModules[$moduleId] ?? false);
    }

    public function moveModuleUp(string $moduleId, ModuleService $moduleService): void
    {
        $this->course->modules()->findOrFail($moduleId);
        abort_unless(auth()->user()->can('modules.edit'), 403);

        $moduleService->moveUp($moduleId);
        $this->course->refresh();
    }

    public function moveModuleDown(string $moduleId, ModuleService $moduleService): void
    {
        $this->course->modules()->findOrFail($moduleId);
        abort_unless(auth()->user()->can('modules.edit'), 403);

        $moduleService->moveDown($moduleId);
        $this->course->refresh();
    }

    public function moveLessonUp(string $lessonId, LessonService $lessonService): void
    {
        abort_unless(auth()->user()->can('lessons.edit'), 403);

        $lessonService->moveUp($lessonId);
        $this->course->refresh();
    }

    public function moveLessonDown(string $lessonId, LessonService $lessonService): void
    {
        abort_unless(auth()->user()->can('lessons.edit'), 403);

        $lessonService->moveDown($lessonId);
        $this->course->refresh();
    }

    public function confirmDelete(string $type, string $id, ModuleService $moduleService, LessonService $lessonService): void
    {
        abort_unless(auth()->user()->can("{$type}.delete"), 403);

        try {
            match ($type) {
                'modules' => $moduleService->delete($id),
                'lessons' => $lessonService->delete($id),
                default => throw new \Exception('Invalid delete type'),
            };

            $this->course->refresh();
            $this->successMessage = __('Deleted successfully.');
        } catch (\Exception $e) {
            $this->errorMessage = __('Failed to delete resource.');
        }
    }

    public function render()
    {
        return view('livewire.courses.course-builder', [
            'modules' => $this->course->modules,
            'isStudent' => $this->isStudent,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
