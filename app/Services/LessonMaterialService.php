<?php

namespace App\Services;

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\User;
use App\Repositories\LessonMaterialRepository;
use App\Repositories\LessonMaterialUserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class LessonMaterialService
{
    public function __construct(
        protected LessonMaterialRepository $materialRepository,
        protected LessonMaterialUserRepository $accessRepository,
        protected R2StorageService $r2Service,
    ) {}

    /**
     * Create a new lesson material with file upload
     */
    public function create(string $lessonId, array $data): LessonMaterial
    {
        $lesson = Lesson::findOrFail($lessonId);

        // Validate material type
        $type = MaterialType::tryFrom($data['type'] ?? '');
        if (! $type) {
            throw new \InvalidArgumentException("Invalid material type: {$data['type']}");
        }

        // Validate file if provided
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $this->validateFile($data['file'], $type);

            // Upload to R2
            $path = "lessons/{$lesson->id}/materials";
            $fileUrl = $this->r2Service->upload($data['file'], $path, $type);
            $fileSize = $data['file']->getSize();
            $mimeType = $data['file']->getMimeType();
            $filePath = $path.'/'.basename($fileUrl);
        } else {
            $fileUrl = $data['file_url'] ?? '';
            $fileSize = $data['file_size'] ?? 0;
            $mimeType = $data['mime_type'] ?? '';
            $filePath = $data['file_path'] ?? '';
        }

        // Get next order
        $order = $this->materialRepository->getNextOrder($lessonId);

        // Create material
        return $this->materialRepository->create([
            'lesson_id' => $lessonId,
            'type' => $type,
            'title' => $data['title'] ?? $data['file']?->getClientOriginalName() ?? 'Untitled',
            'description' => $data['description'] ?? '',
            'file_url' => $fileUrl,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'order' => $order,
        ]);
    }

    /**
     * Update an existing lesson material
     */
    public function update(string $materialId, array $data): LessonMaterial
    {
        $material = LessonMaterial::findOrFail($materialId);

        // If new file provided, upload and delete old one
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $type = $material->type;
            $this->validateFile($data['file'], $type);

            // Delete old file from R2
            if ($material->file_path) {
                $this->r2Service->delete($material->file_path);
            }

            // Upload new file
            $path = "lessons/{$material->lesson_id}/materials";
            $fileUrl = $this->r2Service->upload($data['file'], $path, $type);

            $data['file_url'] = $fileUrl;
            $data['file_size'] = $data['file']->getSize();
            $data['mime_type'] = $data['file']->getMimeType();
            $data['file_path'] = $path.'/'.basename($fileUrl);

            unset($data['file']);
        }

        // Update material
        return $this->materialRepository->update($materialId, $data);
    }

    /**
     * Delete a lesson material and its R2 file
     */
    public function delete(string $materialId): int
    {
        $material = LessonMaterial::findOrFail($materialId);

        // Delete from R2
        if ($material->file_path) {
            $this->r2Service->delete($material->file_path);
        }

        // Delete from database
        return $this->materialRepository->delete($materialId);
    }

    /**
     * Reorder materials within a lesson
     *
     * @param  array<int, string>  $orderedIds  Array of material IDs in desired order
     */
    public function reorder(string $lessonId, array $orderedIds): void
    {
        $this->materialRepository->reorder($lessonId, $orderedIds);
    }

    /**
     * Get all materials for a lesson, ordered
     */
    public function getLessonMaterials(string $lessonId): Collection
    {
        return $this->materialRepository->getByLesson($lessonId);
    }

    /**
     * Get materials of a specific type for a lesson
     */
    public function getLessonMaterialsByType(string $lessonId, MaterialType $type): Collection
    {
        return $this->materialRepository->getByLessonAndType($lessonId, $type);
    }

    /**
     * Mark a material as accessed by a user
     */
    public function markMaterialAsAccessed(string $materialId, User $user): void
    {
        $material = LessonMaterial::findOrFail($materialId);

        // Record access
        $this->accessRepository->markAccessed($materialId, $user->id);

        // Check if lesson is now complete
        $lessonCompletionService = app(LessonCompletionService::class);
        $lessonCompletionService->markLessonIfComplete($material->lesson, $user);
    }

    /**
     * Check if a material is accessed by a user
     */
    public function isMaterialAccessedBy(string $materialId, User $user): bool
    {
        return $this->accessRepository->isAccessedBy($materialId, $user->id);
    }

    /**
     * Get count of accessed materials for a lesson by user
     */
    public function getAccessedMaterialCount(string $lessonId, User $user): int
    {
        return $this->accessRepository->getAccessedCount($lessonId, $user->id);
    }

    /**
     * Validate file against MaterialType limits
     */
    protected function validateFile(UploadedFile $file, MaterialType $type): void
    {
        $maxSize = $type->maxSize();
        $allowedExtensions = $type->allowedExtensions();

        // Check file size
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException(
                "File size exceeds limit for {$type->value}. Max: ".$this->formatBytes($maxSize)
            );
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException(
                "Invalid file extension for {$type->value}. Allowed: ".implode(', ', $allowedExtensions)
            );
        }
    }

    /**
     * Format bytes to human-readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
