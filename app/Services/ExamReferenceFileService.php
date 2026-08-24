<?php

namespace App\Services;

use App\Enums\MaterialType;
use App\Models\ExamReferenceFile;
use App\Repositories\ExamReferenceFile\ExamReferenceFileRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ExamReferenceFileService
{
    public function __construct(
        private ExamReferenceFileRepositoryInterface $repository,
        private R2StorageService $r2Service,
    ) {}

    /**
     * @return Collection<int, ExamReferenceFile>
     */
    public function forAssessmentAndUser(string $assessmentId, string $userId): Collection
    {
        return $this->repository->forAssessmentAndUser($assessmentId, $userId);
    }

    /**
     * @return array{url: string, key: string}
     */
    public function generatePresignedUploadUrl(string $assessmentId, string $userId, string $filename, string $materialType): array
    {
        $this->r2Service->enforceQuotaLimit();

        return $this->r2Service->generatePresignedPutUrlForPath("exam-reference/{$assessmentId}/{$userId}", $filename, $materialType);
    }

    /**
     * Finalize R2 upload from a presigned URL the client already PUT the file to.
     * Runs 3-layer validation: extension (Layer 1, already checked when the
     * upload URL was issued), magic bytes (Layer 2), MIME type (Layer 3).
     *
     * @param  array<string, mixed>  $data
     */
    public function finalizeUpload(string $assessmentId, string $userId, array $data): ExamReferenceFile
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

            $this->r2Service->validateFileContent($localTempPath, $type->value);
            $this->r2Service->validateMimeType($localTempPath, $type->value);

            if ($fileInfo['size'] > $type->maxSize()) {
                throw new \InvalidArgumentException(
                    "This file is too large for a {$type->label()} upload. ".
                    'Maximum allowed size: '.R2StorageService::formatBytes($type->maxSize()).'.'
                );
            }

            $finalKey = $this->r2Service->schoolPrefix()."exam-reference/{$assessmentId}/{$userId}/".substr(hash('sha256', uniqid()), 0, 8).'-'.basename($tempKey);
            $this->r2Service->promoteFromTemp($tempKey, $finalKey);
            $validationPassed = true;

            return $this->repository->create([
                'assessment_id' => $assessmentId,
                'user_id' => $userId,
                'type' => $type,
                'title' => $data['title'] ?? basename($tempKey),
                'file_path' => $finalKey,
                'file_size' => $fileInfo['size'],
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

    public function delete(string $id, string $userId): int
    {
        $file = $this->repository->find($id);

        if ($file === null || $file->user_id !== $userId) {
            return 0;
        }

        $this->r2Service->delete($file->file_path);

        return $this->repository->delete($id);
    }
}
