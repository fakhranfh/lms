<?php

namespace App\Livewire\Courses;

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Services\LessonMaterialService;
use App\Services\LessonService;
use App\Services\R2StorageService;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
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

    #[Validate('nullable|url')]
    public string $videoEmbedUrl = '';

    public Collection $materials;

    public array $uploadProgress = [];

    public ?string $errorMessage = null;

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

        if ($lesson) {
            $this->lesson = $lesson;
            $this->title = $lesson->title;
            $this->content = $lesson->content ?? '';
            $this->durationMinutes = $lesson->duration_minutes;
            $this->isPublished = $lesson->is_published;
            $this->loadMaterials();
        }
    }

    private function loadMaterials(): void
    {
        if ($this->lesson) {
            $this->materials = $this->lesson->materials()->orderBy('order')->get();
            $this->videoEmbedUrl = $this->lesson->video_embed_url ?? '';
        }
    }

    public function save(LessonService $lessonService)
    {
        $this->validate();

        if ($this->videoEmbedUrl && ! $this->isValidVideoUrl($this->videoEmbedUrl)) {
            $this->addError('videoEmbedUrl', 'The video embed URL must be a YouTube or Vimeo link.');

            return;
        }

        $durationMinutes = $this->durationMinutes ? (int) $this->durationMinutes : null;

        if ($this->lesson) {
            $lessonService->update($this->lesson->id, [
                'title' => $this->title,
                'content' => $this->content,
                'video_embed_url' => $this->videoEmbedUrl,
                'duration_minutes' => $durationMinutes,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('lesson-updated');
        } else {
            $lessonService->create([
                'module_id' => $this->module->id,
                'title' => $this->title,
                'content' => $this->content,
                'video_embed_url' => $this->videoEmbedUrl,
                'duration_minutes' => $durationMinutes,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('lesson-created');
        }

        return redirect()->route('courses.show', $this->module->course);
    }

    private function isValidVideoUrl(string $url): bool
    {
        $youtubePatterns = [
            'youtube\.com\/watch\?v=',
            'youtube\.com\/embed\/',
            'youtu\.be\/',
        ];

        $vimeoPatterns = [
            'vimeo\.com\/',
            'player\.vimeo\.com\/video\/',
        ];

        $allPatterns = array_merge($youtubePatterns, $vimeoPatterns);
        foreach ($allPatterns as $pattern) {
            if (preg_match("/$pattern/i", $url)) {
                return true;
            }
        }

        return false;
    }

    public function generateUploadUrl(string $filename, string $materialType, R2StorageService $r2Service): array
    {
        if (! $this->lesson) {
            throw new \InvalidArgumentException('Lesson must be saved before uploading materials');
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

    public function finalizeUpload(array $data, LessonMaterialService $materialService): void
    {
        try {
            if (! $this->lesson) {
                throw new \InvalidArgumentException('Lesson must be saved before uploading materials');
            }

            $material = $materialService->finalizeR2Upload($this->lesson->id, $data);
            $this->materials->push($material);
            $this->errorMessage = null;
            $this->dispatch('material-uploaded', materialId: $material->id);
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

    public function getStorageQuota(R2StorageService $r2Service): array
    {
        return $r2Service->checkSchoolQuota(auth()->user()->school_id);
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
        };
    }

    public function render()
    {
        return view('livewire.courses.lesson-form', [
            'pageTitle' => $this->lesson ? 'Edit Lesson' : 'Create Lesson',
            'materialTypes' => MaterialType::cases(),
            'quota' => $this->getStorageQuota(app(R2StorageService::class)),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->lesson ? 'Edit Lesson' : 'Create Lesson'])
            ->section('app-content');
    }
}
