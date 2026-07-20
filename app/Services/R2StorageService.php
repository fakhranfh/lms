<?php

namespace App\Services;

use App\Enums\MaterialType;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;

class R2StorageService
{
    protected S3Client $s3Client;

    public string $bucket;

    public string $accountId;

    protected string $customDomain;

    protected const GLOBAL_QUOTA_BYTES = 10 * 1024 * 1024 * 1024; // 10 GB

    public function __construct()
    {
        $this->accountId = config('services.r2.account_id');
        $this->bucket = config('services.r2.bucket');
        $this->customDomain = config('services.r2.custom_domain', '');

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://{$this->accountId}.r2.cloudflarestorage.com",
            'credentials' => [
                'key' => config('services.r2.access_key_id'),
                'secret' => config('services.r2.secret_access_key'),
            ],
            'use_path_style_endpoint' => true,
        ]);
    }

    /**
     * Upload file to R2 and return public URL
     */
    public function upload(UploadedFile $file, string $path, MaterialType $type): string
    {
        try {
            // Enforce global quota before upload
            $this->enforceQuotaLimit();

            // Build S3 key (path/filename)
            $key = $this->buildS3Key($path, $file->getClientOriginalName());

            // Upload to R2
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => fopen($file->getRealPath(), 'r'),
                'ContentType' => $file->getMimeType(),
            ]);

            // Return public URL
            return $this->getPublicUrl($key);
        } catch (AwsException $e) {
            throw new \Exception("R2 Upload failed: {$e->getMessage()}");
        }
    }

    /**
     * Delete file from R2
     */
    public function delete(string $filePath): bool
    {
        try {
            // Extract key from URL or use as-is if already a key
            $key = $this->extractKeyFromPath($filePath);

            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (AwsException $e) {
            \Log::error("R2 Delete failed: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Get signed URL for direct downloads (future feature)
     */
    public function getSignedUrl(string $filePath, int $expiresIn = 3600): string
    {
        try {
            $key = $this->extractKeyFromPath($filePath);

            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, "+{$expiresIn} seconds");

            return (string) $request->getUri();
        } catch (AwsException $e) {
            throw new \Exception("Failed to generate signed URL: {$e->getMessage()}");
        }
    }

    /**
     * Generate presigned PUT URL for direct client upload to R2 (temp staging)
     * Validates file extension BEFORE generating URL (Layer 1)
     * File will be uploaded to temp/ folder first for content validation
     *
     * @return array{url: string, key: string, lesson_id: string}
     */
    public function generatePresignedPutUrl(string $lessonId, string $filename, string $materialType, int $expiresIn = 3600): array
    {
        try {
            // Layer 1: Validate extension BEFORE generating URL (server-side validation)
            $this->validateFileExtension($filename, $materialType);

            // Enforce quota
            $this->enforceQuotaLimit();

            // Upload to temp folder first (content validation happens in finalizeR2Upload)
            $key = "temp/{$lessonId}/".substr(hash('sha256', uniqid()), 0, 8).'-'.$filename;

            $cmd = $this->s3Client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, "+{$expiresIn} seconds");
            $presignedUrl = (string) $request->getUri();

            return [
                'url' => $presignedUrl,
                'key' => $key,
                'lesson_id' => $lessonId,
            ];
        } catch (AwsException $e) {
            throw new \Exception("Failed to generate presigned PUT URL: {$e->getMessage()}");
        }
    }

    /**
     * Layer 1: Validate file extension against material type
     */
    public function validateFileExtension(string $filename, string $materialType): void
    {
        try {
            $materialTypeEnum = MaterialType::from($materialType);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException("Invalid material type: {$materialType}");
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = $materialTypeEnum->allowedExtensions();

        if (! in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException(
                "Invalid file extension '.{$extension}' for {$materialTypeEnum->value}. "
                .'Allowed: '.implode(', ', $allowedExtensions)
            );
        }
    }

    /**
     * Download file from R2 to local temp storage
     */
    public function downloadToLocalTemp(string $key): string
    {
        try {
            $tempPath = sys_get_temp_dir().'/r2-'.substr(hash('sha256', uniqid()), 0, 8).'-'.basename($key);

            $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SaveAs' => $tempPath,
            ]);

            return $tempPath;
        } catch (AwsException $e) {
            throw new \Exception("Failed to download file from R2: {$e->getMessage()}");
        }
    }

    /**
     * Layer 2: Validate file content (magic bytes signature)
     */
    public function validateFileContent(string $localPath, string $materialType): void
    {
        try {
            $materialTypeEnum = MaterialType::from($materialType);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException("Invalid material type: {$materialType}");
        }

        $magicBytesToCheck = $materialTypeEnum->magicBytes();

        // Skip check for formats without reliable magic bytes (text-based)
        if (empty($magicBytesToCheck)) {
            return;
        }

        // Read file header
        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            throw new \Exception("Cannot read file: {$localPath}");
        }

        $headerBytes = fread($handle, 512); // Read first 512 bytes
        fclose($handle);

        if ($headerBytes === false) {
            throw new \Exception('Failed to read file header');
        }

        // Convert to hex
        $headerHex = bin2hex($headerBytes);

        // Check if any magic bytes match. Most formats place their signature at
        // byte 0, but MP4/MOV containers prefix it with a 4-byte box-size field
        // (e.g. "ftyp" sits at offset 4), so search within the first 16 bytes
        // rather than requiring an exact match at the very start of the file.
        $foundMatch = false;
        $searchWindow = substr($headerHex, 0, 32);
        foreach ($magicBytesToCheck as $magicHex) {
            if (str_contains($searchWindow, $magicHex)) {
                $foundMatch = true;
                break;
            }
        }

        if (! $foundMatch) {
            throw new \InvalidArgumentException(
                "This file doesn't look like a valid {$materialTypeEnum->label()} file. ".
                'It may be corrupted, or its extension was changed to something it isn\'t. '.
                'Please check the file and try again.'
            );
        }
    }

    /**
     * Layer 3: Validate MIME type using finfo
     */
    public function validateMimeType(string $localPath, string $materialType): void
    {
        try {
            $materialTypeEnum = MaterialType::from($materialType);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException("Invalid material type: {$materialType}");
        }

        $allowedMimeTypes = $materialTypeEnum->allowedMimeTypes();

        // Get actual MIME type from file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new \Exception('Cannot initialize finfo');
        }

        $actualMime = finfo_file($finfo, $localPath);
        finfo_close($finfo);

        if ($actualMime === false) {
            throw new \Exception('Cannot determine file MIME type');
        }

        // Normalize MIME type (remove charset, etc.)
        $actualMime = strtolower(explode(';', $actualMime)[0]);

        // Check if MIME type is allowed
        $mimeTypeMatches = in_array($actualMime, array_map('strtolower', $allowedMimeTypes));

        if (! $mimeTypeMatches) {
            throw new \InvalidArgumentException(
                "This file isn't a supported {$materialTypeEnum->label()} file. ".
                'Please upload a file in one of these formats: '.
                implode(', ', $materialTypeEnum->allowedExtensions()).'.'
            );
        }
    }

    /**
     * Promote file from temp folder to final location in R2
     * Uses server-side copy (no re-upload needed)
     */
    public function promoteFromTemp(string $tempKey, string $finalKey): void
    {
        try {
            // Copy from temp to final
            $this->s3Client->copyObject([
                'Bucket' => $this->bucket,
                'CopySource' => "{$this->bucket}/{$tempKey}",
                'Key' => $finalKey,
            ]);

            // Delete temp object
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $tempKey,
            ]);
        } catch (AwsException $e) {
            throw new \Exception("Failed to promote file from temp: {$e->getMessage()}");
        }
    }

    /**
     * Delete temporary R2 object (cleanup on validation failure)
     */
    public function deleteTempObject(string $tempKey): bool
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $tempKey,
            ]);

            return true;
        } catch (AwsException $e) {
            \Log::error("Failed to delete temp R2 object {$tempKey}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Verify file exists in R2 and get its metadata
     */
    public function verifyFileExists(string $fileUrl): array
    {
        try {
            $key = $this->extractKeyFromPath($fileUrl);

            $object = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return [
                'exists' => true,
                'size' => $object['ContentLength'] ?? 0,
                'mime_type' => $object['ContentType'] ?? 'application/octet-stream',
                'last_modified' => $object['LastModified'] ?? now(),
            ];
        } catch (AwsException $e) {
            if ($e->getStatusCode() === 404) {
                return ['exists' => false];
            }
            throw new \Exception("Failed to verify file: {$e->getMessage()}");
        }
    }

    /**
     * Check storage quota for a school
     *
     * @return array{used: int, limit: int, remaining: int, percentage: float}
     */
    public function checkSchoolQuota(string $schoolId): array
    {
        $used = $this->getTotalStorageUsed();
        $limit = self::GLOBAL_QUOTA_BYTES;
        $remaining = max(0, $limit - $used);
        $percentage = $limit > 0 ? ((float) $used / $limit) * 100 : 0.0;

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'percentage' => $percentage,
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Enforce global quota limit before allowing uploads
     */
    public function enforceQuotaLimit(): void
    {
        $quota = $this->checkSchoolQuota('');
        if ($quota['remaining'] <= 0) {
            throw new \Exception('System storage quota reached (10 GB). Contact admin.');
        }
    }

    /**
     * Get total storage used across all R2 objects
     */
    public function getTotalStorageUsed(): int
    {
        try {
            $total = 0;
            $paginator = $this->s3Client->getPaginator('ListObjectsV2', [
                'Bucket' => $this->bucket,
            ]);

            foreach ($paginator as $result) {
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $total += $object['Size'] ?? 0;
                    }
                }
            }

            return $total;
        } catch (AwsException $e) {
            \Log::error("Failed to get total storage used: {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Build S3 key with path and filename
     */
    protected function buildS3Key(string $path, string $filename): string
    {
        $timestamp = now()->format('YmdHis');
        $hash = substr(hash('sha256', $filename.$timestamp), 0, 8);

        return "{$path}/{$hash}-{$filename}";
    }

    /**
     * Extract S3 key from URL or return as-is
     */
    public function extractKeyFromPath(string $filePath): string
    {
        // If it's a full URL, extract key from it
        if (str_contains($filePath, 'r2.cloudflarestorage.com') || str_contains($filePath, 'https://')) {
            // Parse URL to get path
            $parsed = parse_url($filePath);

            return ltrim($parsed['path'] ?? '', '/');
        }

        // If it's a custom domain URL
        if ($this->customDomain && str_contains($filePath, $this->customDomain)) {
            $parsed = parse_url($filePath);

            return ltrim($parsed['path'] ?? '', '/');
        }

        // Assume it's already a key
        return $filePath;
    }

    /**
     * Get public URL for a file
     */
    public function getPublicUrl(string $key): string
    {
        if ($this->customDomain) {
            return "{$this->customDomain}/{$key}";
        }

        return "https://{$this->bucket}.{$this->accountId}.r2.cloudflarestorage.com/{$key}";
    }
}
