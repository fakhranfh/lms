<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\School;
use App\Services\LessonMaterialService;
use App\Services\R2StorageService;
use App\Services\StorageMonitoringService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminStorageMaterials extends Component
{
    use WithPagination;

    #[Url(as: 'school')]
    public ?string $schoolId = null;

    public ?string $courseId = null;

    public ?string $moduleId = null;

    public ?string $lessonId = null;

    public ?string $search = null;

    public string $sort = 'created_at';

    public string $direction = 'desc';

    public int $perPage = 15;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('analytics.view'), 403);
    }

    public function updatedSchoolId(): void
    {
        $this->courseId = null;
        $this->moduleId = null;
        $this->lessonId = null;
        $this->resetPage();
    }

    public function updatedCourseId(): void
    {
        $this->moduleId = null;
        $this->lessonId = null;
        $this->resetPage();
    }

    public function updatedModuleId(): void
    {
        $this->lessonId = null;
        $this->resetPage();
    }

    public function updatedLessonId(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['schoolId', 'courseId', 'moduleId', 'lessonId', 'search']);
        $this->resetPage();
    }

    public function deleteMaterial(string $materialId, LessonMaterialService $materialService): void
    {
        abort_unless(auth()->user()->can('analytics.view'), 403);

        $materialService->delete($materialId);
    }

    #[Computed]
    public function schoolOptions(): Collection
    {
        return School::orderBy('name')->get();
    }

    #[Computed]
    public function courseOptions(): Collection
    {
        if (! $this->schoolId) {
            return collect();
        }

        return Course::where('school_id', $this->schoolId)->orderBy('title')->get();
    }

    #[Computed]
    public function moduleOptions(): Collection
    {
        if (! $this->courseId) {
            return collect();
        }

        return Module::where('course_id', $this->courseId)->orderBy('title')->get();
    }

    #[Computed]
    public function lessonOptions(): Collection
    {
        if (! $this->moduleId) {
            return collect();
        }

        return Lesson::where('module_id', $this->moduleId)->orderBy('title')->get();
    }

    #[Computed]
    public function materials(): LengthAwarePaginator
    {
        return app(StorageMonitoringService::class)
            ->filteredMaterialsQuery([
                'school_id' => $this->schoolId,
                'course_id' => $this->courseId,
                'module_id' => $this->moduleId,
                'lesson_id' => $this->lessonId,
                'title' => $this->search,
            ], $this->sort, $this->direction)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.admin-storage-materials', [
            'formatBytes' => fn (int $bytes) => R2StorageService::formatBytes($bytes),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
