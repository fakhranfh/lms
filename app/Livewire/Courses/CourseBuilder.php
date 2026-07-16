<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\LessonService;
use App\Services\ModuleService;
use Livewire\Attributes\On;
use Livewire\Component;

class CourseBuilder extends Component
{
    public Course $course;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $showDeleteModal = false;

    public ?string $deleteType = null;

    public ?string $deleteId = null;

    public ?string $deleteName = null;

    /** @var array<string, bool> */
    public array $expandedModules = [];

    public function mount(Course $course): void
    {
        abort_unless(auth()->user()->can('courses.view') && $course->school_id === auth()->user()->school_id, 403);
        $this->course = $course;
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

    public function showDeleteConfirm(string $type, string $id, string $name): void
    {
        abort_unless(auth()->user()->can("{$type}.delete"), 403);

        $this->deleteType = $type;
        $this->deleteId = $id;
        $this->deleteName = $name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(ModuleService $moduleService, LessonService $lessonService): void
    {
        if (! $this->deleteType || ! $this->deleteId) {
            return;
        }

        try {
            match ($this->deleteType) {
                'modules' => $moduleService->delete($this->deleteId),
                'lessons' => $lessonService->delete($this->deleteId),
                default => throw new \Exception('Invalid delete type'),
            };

            $this->course->refresh();
            $this->showDeleteModal = false;
            $this->deleteType = null;
            $this->deleteId = null;
            $this->deleteName = null;
            $this->successMessage = __('Deleted successfully.');
        } catch (\Exception $e) {
            $this->errorMessage = __('Failed to delete resource.');
        }
    }

    public function render()
    {
        return view('livewire.courses.course-builder', [
            'modules' => $this->course->modules,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
