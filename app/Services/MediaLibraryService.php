<?php

namespace App\Services;

use App\Enums\MaterialType;
use App\Models\MediaLibraryItem;
use App\Repositories\MediaLibrary\MediaLibraryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class MediaLibraryService
{
    public function __construct(
        protected MediaLibraryRepositoryInterface $repository,
        protected R2StorageService $r2Service,
    ) {}

    /**
     * Generate presigned PUT URL for direct R2 upload (client-side flow), scoped
     * to the school's media library rather than a lesson.
     * Validates file extension BEFORE generating URL and enforces school quota.
     *
     * @return array{url: string, key: string}
     */
    public function generatePresignedUploadUrl(string $schoolId, string $filename, string $materialType): array
    {
        $this->r2Service->enforceQuotaLimit($schoolId);

        return $this->r2Service->generatePresignedPutUrlForPath('media', $filename, $materialType);
    }

    /**
     * Finalize R2 upload from presigned URL (client uploaded to temp/media/ first)
     * Runs 3-layer validation: extension (Layer 1), magic bytes (Layer 2), MIME type (Layer 3)
     * Promotes file from temp to final location if all validations pass.
     *
     * @param  array<string, mixed>  $data
     */
    public function finalizeUpload(string $schoolId, ?string $uploadedBy, array $data): MediaLibraryItem
    {
        $type = MaterialType::tryFrom($data['type'] ?? '');
        if (! $type) {
            throw new \InvalidArgumentException("Invalid material type: {$data['type']}");
        }

        $tempKey = $data['temp_key'] ?? '';
        if (! $tempKey) {
            throw new \InvalidArgumentException('temp_key is required');
        }

        $fileInfo = $this->r2Service->verifyFileExists($tempKey);
        if (! $fileInfo['exists']) {
            throw new \Exception("Temp file not found in R2: {$tempKey}");
        }

        $localTempPath = null;
        $validationPassed = false;

        try {
            $localTempPath = $this->r2Service->downloadToLocalTemp($tempKey);

            // Layer 2: Validate file content (magic bytes)
            $this->r2Service->validateFileContent($localTempPath, $type->value);

            // Layer 3: Validate MIME type
            $this->r2Service->validateMimeType($localTempPath, $type->value);

            if ($fileInfo['size'] > $type->maxSize()) {
                throw new \InvalidArgumentException(
                    "This file is too large for a {$type->label()} upload. ".
                    'Maximum allowed size: '.$this->formatBytes($type->maxSize()).'.'
                );
            }

            $finalKey = $this->r2Service->schoolPrefix().'media/'.substr(hash('sha256', uniqid()), 0, 8).'-'.basename($tempKey);
            $this->r2Service->promoteFromTemp($tempKey, $finalKey);
            $validationPassed = true;

            return $this->repository->create([
                'school_id' => $schoolId,
                'uploaded_by' => $uploadedBy,
                'type' => $type,
                'title' => $data['title'] ?? 'Untitled',
                'description' => $data['description'] ?? '',
                'file_path' => $finalKey,
                'file_size' => $fileInfo['size'],
                'mime_type' => $fileInfo['mime_type'],
            ]);
        } finally {
            if ($localTempPath && file_exists($localTempPath)) {
                @unlink($localTempPath);
            }

            if (! $validationPassed) {
                $this->r2Service->deleteTempObject($tempKey);
            }
        }
    }

    /**
     * Upload raw file content directly to R2 (no presigned/temp flow) and
     * create its MediaLibraryItem. Used for dev/test tooling that generates
     * material files server-side rather than through a real user upload.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFromRawContent(string $schoolId, ?string $uploadedBy, string $key, string $content, string $contentType, array $data): MediaLibraryItem
    {
        $this->r2Service->uploadRawContent($key, $content, $contentType);

        return $this->repository->create([
            'school_id' => $schoolId,
            'uploaded_by' => $uploadedBy,
            'type' => $data['type'],
            'title' => $data['title'] ?? 'Untitled',
            'description' => $data['description'] ?? '',
            'file_path' => $key,
            'file_size' => strlen($content),
            'mime_type' => $contentType,
        ]);
    }

    /**
     * Update a media library item's metadata (title/description only).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMetadata(string $id, array $data): MediaLibraryItem
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete a media library item and its R2 file.
     */
    public function delete(string $id): int
    {
        $item = $this->repository->findOrFail($id);

        if ($item->file_path) {
            $this->r2Service->delete($item->file_path);
        }

        return $this->repository->delete($id);
    }

    public function list(string $schoolId, ?string $type = null, ?string $search = null): Builder
    {
        return $this->repository->filteredQuery($schoolId, $type, $search);
    }

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
