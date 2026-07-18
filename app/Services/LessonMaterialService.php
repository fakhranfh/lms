<?php

namespace App\Services;

use App\Enums\MaterialType;
use App\Models\LessonMaterial;
use App\Models\User;
use App\Repositories\Lesson\LessonRepository;
use App\Repositories\LessonMaterial\LessonMaterialRepository;
use App\Repositories\LessonMaterialUser\LessonMaterialUserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class LessonMaterialService
{
    public function __construct(
        protected LessonRepository $lessonRepository,
        protected LessonMaterialRepository $materialRepository,
        protected LessonMaterialUserRepository $accessRepository,
        protected R2StorageService $r2Service,
    ) {}

    /**
     * Generate presigned PUT URL for direct R2 upload (client-side flow)
     * Validates file extension BEFORE generating URL
     *
     * @param  string  $lessonId  Lesson ID
     * @param  string  $filename  Original filename
     * @param  string  $materialType  Material type (PDF, Video, etc)
     * @return array{url: string, key: string, lesson_id: string}
     */
    public function generatePresignedUploadUrl(string $lessonId, string $filename, string $materialType): array
    {
        $this->lessonRepository->findOrFail($lessonId);

        // Extension validation happens here (server-side, before URL generation)
        return $this->r2Service->generatePresignedPutUrl($lessonId, $filename, $materialType);
    }

    /**
     * Finalize R2 upload from presigned URL (client uploaded to temp/ first)
     * Runs 3-layer validation: extension (Layer 1), magic bytes (Layer 2), MIME type (Layer 3)
     * Promotes file from temp to final location if all validations pass
     * Cleans up on any failure
     */
    public function finalizeR2Upload(string $lessonId, array $data): LessonMaterial
    {
        $lesson = $this->lessonRepository->findOrFail($lessonId);

        // Validate material type
        $type = MaterialType::tryFrom($data['type'] ?? '');
        if (! $type) {
            throw new \InvalidArgumentException("Invalid material type: {$data['type']}");
        }

        // Get temp key from data (e.g., temp/{lessonId}/xxx-filename.pdf)
        $tempKey = $data['temp_key'] ?? '';
        if (! $tempKey) {
            throw new \InvalidArgumentException('temp_key is required');
        }

        // Verify temp file exists in R2
        $fileInfo = $this->r2Service->verifyFileExists($tempKey);
        if (! $fileInfo['exists']) {
            throw new \Exception("Temp file not found in R2: {$tempKey}");
        }

        // Download to local temp for validation
        $localTempPath = null;
        $validationPassed = false;

        try {
            $localTempPath = $this->r2Service->downloadToLocalTemp($tempKey);

            // Layer 2: Validate file content (magic bytes)
            $this->r2Service->validateFileContent($localTempPath, $type->value);

            // Layer 3: Validate MIME type
            $this->r2Service->validateMimeType($localTempPath, $type->value);

            // Validate file size against material type limit
            if ($fileInfo['size'] > $type->maxSize()) {
                throw new \InvalidArgumentException(
                    "File size exceeds limit for {$type->value}. Max: ".$this->formatBytes($type->maxSize())
                );
            }

            // All validations passed - promote file from temp to final location
            $finalKey = "lessons/{$lessonId}/materials/".substr(hash('sha256', uniqid()), 0, 8).'-'.basename($tempKey);
            $this->r2Service->promoteFromTemp($tempKey, $finalKey);
            $validationPassed = true;

            // Build final file URL
            $fileUrl = "https://{$this->r2Service->bucket}.{$this->r2Service->accountId}.r2.cloudflarestorage.com/{$finalKey}";

            // Save material metadata via repository
            return $this->materialRepository->create([
                'lesson_id' => $lessonId,
                'type' => $type,
                'title' => $data['title'] ?? 'Untitled',
                'description' => $data['description'] ?? '',
                'file_url' => $fileUrl,
                'file_path' => $finalKey,
                'file_size' => $fileInfo['size'],
                'mime_type' => $fileInfo['mime_type'],
            ]);

        } finally {
            // Always cleanup local temp file
            if ($localTempPath && file_exists($localTempPath)) {
                @unlink($localTempPath);
            }

            // On validation failure, cleanup R2 temp object too
            if (! $validationPassed) {
                $this->r2Service->deleteTempObject($tempKey);
            }
        }
    }

    /**
     * Create material from direct R2 upload (after client uploads file)
     * Verifies file exists in R2 before saving metadata
     *
     * @deprecated Use finalizeR2Upload() instead for 3-layer validation with temp staging
     */
    public function createFromR2Upload(string $lessonId, array $data): LessonMaterial
    {
        $lesson = $this->lessonRepository->findOrFail($lessonId);

        // Validate material type
        $type = MaterialType::tryFrom($data['type'] ?? '');
        if (! $type) {
            throw new \InvalidArgumentException("Invalid material type: {$data['type']}");
        }

        // Verify file exists in R2
        $fileUrl = $data['file_url'] ?? '';
        if (! $fileUrl) {
            throw new \InvalidArgumentException('file_url is required');
        }

        $fileInfo = $this->r2Service->verifyFileExists($fileUrl);
        if (! $fileInfo['exists']) {
            throw new \Exception("File not found in R2: {$fileUrl}");
        }

        // Validate file size against material type limit
        if ($fileInfo['size'] > $type->maxSize()) {
            throw new \InvalidArgumentException(
                "File size exceeds limit for {$type->value}. Max: ".$this->formatBytes($type->maxSize())
            );
        }

        // Get next order
        $order = $this->materialRepository->getNextOrder($lessonId);

        // Create material with R2 file info
        return $this->materialRepository->create([
            'lesson_id' => $lessonId,
            'type' => $type,
            'title' => $data['title'] ?? 'Untitled',
            'description' => $data['description'] ?? '',
            'file_url' => $fileUrl,
            'file_path' => $this->r2Service->extractKeyFromPath($fileUrl),
            'file_size' => $fileInfo['size'],
            'mime_type' => $fileInfo['mime_type'],
            'order' => $order,
        ]);
    }

    /**
     * Create a new lesson material with file upload (server-side flow)
     * Legacy: client uploads to server, server uploads to R2
     */
    public function create(string $lessonId, array $data): LessonMaterial
    {
        $lesson = $this->lessonRepository->findOrFail($lessonId);

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
