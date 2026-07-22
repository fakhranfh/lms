<?php

namespace App\Livewire\Courses;

use App\Enums\MaterialType;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Services\AssignmentService;
use App\Services\LessonMaterialService;
use App\Services\LessonService;
use App\Services\R2StorageService;
use App\Services\StorageMonitoringService;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LessonForm extends Component
{
    public Module $module;

    public ?Lesson $lesson = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public string $content = '';

    #[Validate('nullable|integer|min:1|max:480')]
    public ?string $durationMinutes = null;

    public bool $isPublished = false;

    public Collection $materials;

    public Collection $materialVersions;

    public Collection $assignments;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(CurrentSchool $currentSchool, ?Module $module = null, ?Lesson $lesson = null): void
    {
        abort_unless(auth()->user()->can('lessons.create') || auth()->user()->can('lessons.edit'), 403);

        $module ??= $lesson?->module;

        abort_if($module === null, 404);

        if (! $module->relationLoaded('course')) {
            $module->load('course');
        }

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($module->course->school_id === $schoolId, 403);

        $this->module = $module;
        $this->materials = new Collection;
        $this->materialVersions = new Collection;
        $this->assignments = new Collection;

        if ($lesson) {
            $this->lesson = $lesson;
            $this->title = $lesson->title;
            $this->content = $lesson->content ?? '';
            $this->durationMinutes = $lesson->duration_minutes;
            $this->isPublished = $lesson->is_published;
            $this->loadMaterials();
            $this->loadAssignments();
        }
    }

    private function loadAssignments(): void
    {
        if ($this->lesson) {
            $this->assignments = $this->lesson->assignments()->latest()->get();
        }
    }

    #[On('delete-confirmed')]
    public function deleteAssignment(string $id, AssignmentService $assignmentService): void
    {
        abort_unless(auth()->user()->can('assignments.delete'), 403);

        $assignment = Assignment::find($id);

        if (! $assignment || $assignment->lesson_id !== $this->lesson?->id) {
            return;
        }

        $assignmentService->delete($id);
        $this->assignments = $this->assignments->reject(fn (Assignment $a) => $a->id === $id);
        $this->successMessage = __('Assignment deleted successfully.');
    }

    private function loadMaterials(): void
    {
        if ($this->lesson) {
            $this->materials = $this->lesson->materials()->active()->orderBy('order')->get();
        }
    }

    public function save(LessonService $lessonService)
    {
        $this->validate();

        $durationMinutes = $this->durationMinutes ? (int) $this->durationMinutes : null;

        if ($this->lesson) {
            $lessonService->update($this->lesson->id, [
                'title' => $this->title,
                'content' => $this->content,
                'duration_minutes' => $durationMinutes,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('lesson-updated');
        } else {
            $lessonService->create([
                'module_id' => $this->module->id,
                'title' => $this->title,
                'content' => $this->content,
                'duration_minutes' => $durationMinutes,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('lesson-created');
        }

        return redirect()->route('courses.show', $this->module->course);
    }

    /**
     * Silently persist the lesson as a draft so materials can be attached
     * before the user explicitly submits the form.
     *
     * @return array{error?: string}
     */
    private function ensureLessonExists(LessonService $lessonService): array
    {
        if ($this->lesson) {
            return [];
        }

        if (trim($this->title) === '') {
            return ['error' => 'Please enter a lesson title before uploading materials.'];
        }

        $this->lesson = $lessonService->create([
            'module_id' => $this->module->id,
            'title' => $this->title,
            'content' => $this->content,
            'duration_minutes' => $this->durationMinutes ? (int) $this->durationMinutes : null,
            'is_published' => false,
        ]);

        $this->loadMaterials();

        return [];
    }

    public function generateUploadUrl(string $filename, string $materialType, R2StorageService $r2Service, LessonService $lessonService): array
    {
        $draft = $this->ensureLessonExists($lessonService);
        if (isset($draft['error'])) {
            return $draft;
        }

        try {
            $type = MaterialType::tryFrom($materialType);
            if (! $type) {
                return ['error' => 'Invalid material type'];
            }

            $url = $r2Service->generatePresignedPutUrl($this->lesson->id, $filename, $materialType);

            return $url;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Generate a presigned upload URL for replacing an existing material's file
     * (creates a new version instead of a brand-new material)
     *
     * @return array{url?: string, key?: string, error?: string}
     */
    public function generateVersionUploadUrl(string $materialId, string $filename, R2StorageService $r2Service): array
    {
        try {
            $material = LessonMaterial::findOrFail($materialId);

            $r2Service->enforceQuotaLimit($this->getSchoolId());

            return $r2Service->generatePresignedPutUrl($this->lesson->id, $filename, $material->type->value);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Finalize a new version upload for an existing material
     *
     * @return array{error?: string}
     */
    public function finalizeVersionUpload(string $materialId, array $data, LessonMaterialService $materialService): array
    {
        try {
            $materialService->finalizeVersionUpload($materialId, $data);
            $this->loadMaterials();
            $this->errorMessage = null;
            $this->dispatch('version-uploaded', materialId: $materialId);

            return [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @return array{error?: string}
     */
    public function finalizeUpload(array $data, LessonMaterialService $materialService): array
    {
        try {
            if (! $this->lesson) {
                throw new \InvalidArgumentException('Lesson must be saved before uploading materials');
            }

            $material = $materialService->finalizeR2Upload($this->lesson->id, $data);
            $this->materials->push($material);
            $this->errorMessage = null;
            $this->dispatch('material-uploaded', materialId: $material->id);

            return [];
        } catch (\Exception $e) {
            // Reported back to the JS uploader (clientError) instead of $errorMessage,
            // which would otherwise render the same message a second time server-side.
            return ['error' => $e->getMessage()];
        }
    }

    public function updateMaterialTitle(string $materialId, string $title, LessonMaterialService $materialService): void
    {
        try {
            if (trim($title) === '') {
                return;
            }

            $material = $materialService->update($materialId, ['title' => trim($title)]);
            $this->materials = $this->materials->map(
                fn (LessonMaterial $m) => $m->id === $material->id ? $material : $m
            );
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function deleteMaterial(string $materialId, LessonMaterialService $materialService): void
    {
        try {
            $materialService->delete($materialId);
            $this->materials = $this->materials->reject(fn (LessonMaterial $m) => $m->id === $materialId);
            $this->dispatch('material-deleted', materialId: $materialId);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function reorderMaterials(array $materialIds, LessonMaterialService $materialService): void
    {
        try {
            if (! $this->lesson) {
                return;
            }

            $materialService->reorder($this->lesson->id, $materialIds);
            $this->loadMaterials();
            $this->dispatch('materials-reordered');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Get all versions of a material
     *
     * @return Collection<int, LessonMaterial>
     */
    public function getMaterialVersions(string $materialId): Collection
    {
        try {
            return app(LessonMaterialService::class)->getAllVersions($materialId);
        } catch (\Exception $e) {
            return new Collection;
        }
    }

    /**
     * Switch to a different version of a material
     */
    public function switchToVersion(string $materialId, int $targetVersion, LessonMaterialService $materialService): void
    {
        try {
            $materialService->switchVersion($materialId, $targetVersion);
            $this->loadMaterials();
            $this->dispatch('version-switched', materialId: $materialId, version: $targetVersion);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Delete a specific version of a material
     */
    public function deleteVersion(string $materialId, int $versionToDelete, LessonMaterialService $materialService): void
    {
        try {
            $materialService->deleteVersion($materialId, $versionToDelete);
            $this->loadMaterials();
            $this->dispatch('version-deleted', materialId: $materialId, version: $versionToDelete);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    public function getMaterialIcon(MaterialType $type): string
    {
        return match ($type) {
            MaterialType::Video => '🎥',
            MaterialType::PDF => '📄',
            MaterialType::Document => '📝',
            MaterialType::Audio => '🎵',
            MaterialType::Presentation => '📊',
            MaterialType::Image => '🖼️',
            MaterialType::Interactive => '🎮',
            MaterialType::Markdown => '📄',
        };
    }

    /**
     * Get school ID from module
     */
    private function getSchoolId(): ?string
    {
        try {
            // Handle case where module is not initialized (e.g., in tests)
            if (! isset($this->module) || ! $this->module) {
                return null;
            }

            if (! $this->module->relationLoaded('course')) {
                $this->module->load('course');
            }

            return $this->module->course->school_id ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get quota information for display in the UI
     * Shows remaining storage and usage percentage with color coding
     * Based on school's tier limits
     *
     * @return array{used: string, remaining: string, percentage: float, color: string, warning: string|null, limit_gb: int|null, global_percentage: float}
     */
    public function getQuotaInfo(?R2StorageService $r2Service = null, ?StorageMonitoringService $monitoringService = null): array
    {
        $r2Service ??= app(R2StorageService::class);
        $monitoringService ??= app(StorageMonitoringService::class);

        $schoolId = $this->getSchoolId();
        $quota = $r2Service->checkSchoolQuota($schoolId);
        $percentage = $quota['percentage'];
        $globalPercentage = $monitoringService->globalSummary()['percentage'];

        // Determine warning level and color based on usage
        $color = match (true) {
            $percentage < 80 => 'text-green-600',
            $percentage < 90 => 'text-yellow-600',
            $percentage < 100 => 'text-orange-600',
            default => 'text-red-600',
        };

        $warning = match (true) {
            $percentage >= 100 => '❌ Quota full. Uploads blocked.',
            $percentage >= 90 => '⚠️ Quota at 90%. Upload may fail soon.',
            $percentage >= 80 => '⚠️ Quota at 80%. Consider freeing space.',
            default => null,
        };

        return [
            'used' => $this->formatBytes($quota['used']),
            'remaining' => $this->formatBytes($quota['remaining']),
            'percentage' => round($percentage, 1),
            'color' => $color,
            'warning' => $warning,
            'can_upload' => $percentage < 100,
            'limit_gb' => $quota['limit_gb'] ?? null,
            'global_percentage' => round($globalPercentage, 1),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getExtensionToTypeMap(): array
    {
        $map = [];
        foreach (MaterialType::cases() as $type) {
            foreach ($type->allowedExtensions() as $extension) {
                $map[$extension] = $type->value;
            }
        }

        return $map;
    }

    public function render()
    {
        $extensionTypeMap = $this->getExtensionToTypeMap();

        return view('livewire.courses.lesson-form', [
            'pageTitle' => $this->lesson ? 'Edit Lesson' : 'Create Lesson',
            'materialTypes' => MaterialType::cases(),
            'extensionTypeMap' => $extensionTypeMap,
            'acceptedExtensions' => implode(',', array_map(fn (string $ext) => ".{$ext}", array_keys($extensionTypeMap))),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->lesson ? 'Edit Lesson' : 'Create Lesson'])
            ->section('app-content');
    }
}
