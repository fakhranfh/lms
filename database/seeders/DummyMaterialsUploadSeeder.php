<?php

namespace Database\Seeders;

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Services\R2StorageService;
use Aws\S3\S3Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyMaterialsUploadSeeder extends Seeder
{
    private S3Client $s3Client;

    private R2StorageService $r2Service;

    private string $bucket;

    private string $accountId;

    private string $customDomain;

    public function __construct()
    {
        $this->accountId = config('services.r2.account_id');
        $this->bucket = config('services.r2.bucket');
        $this->customDomain = config('services.r2.custom_domain', '');
        $this->r2Service = new R2StorageService;

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

    public function run(): void
    {
        $lessons = Lesson::with(['module.course'])->get();

        if ($lessons->isEmpty()) {
            $this->command->warn('No lessons found. Skipping material upload.');

            return;
        }

        $basePath = storage_path('dummy-materials');
        if (! is_dir($basePath)) {
            $this->command->warn("Dummy materials folder not found at {$basePath}");

            return;
        }

        $files = $this->getDummyFiles($basePath);
        if (empty($files)) {
            $this->command->warn('No dummy material files found.');

            return;
        }

        foreach ($lessons as $lesson) {
            $this->seedLessonMaterials($lesson, $files);
        }

        $this->command->info('Dummy materials successfully uploaded to R2 and linked to lessons.');
    }

    private function getDummyFiles(string $basePath): array
    {
        $files = [];
        $dummyPath = $basePath;

        if (! is_dir($dummyPath)) {
            return [];
        }

        $scanDir = scandir($dummyPath);
        if ($scanDir === false) {
            return [];
        }

        foreach ($scanDir as $file) {
            if ($file === '.' || $file === '..' || is_dir("$dummyPath/$file")) {
                continue;
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $materialType = $this->getMaterialTypeFromExtension($extension);

            if ($materialType !== null) {
                $files[] = [
                    'name' => $file,
                    'path' => "$dummyPath/$file",
                    'extension' => $extension,
                    'type' => $materialType,
                ];
            }
        }

        return $files;
    }

    private function getMaterialTypeFromExtension(string $extension): ?MaterialType
    {
        return match ($extension) {
            'mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv' => MaterialType::Video,
            'pdf' => MaterialType::PDF,
            'doc', 'docx', 'txt', 'rtf', 'odt' => MaterialType::Document,
            'mp3', 'wav', 'm4a', 'flac' => MaterialType::Audio,
            'ppt', 'pptx', 'odp' => MaterialType::Presentation,
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => MaterialType::Image,
            'html', 'htm', 'json' => MaterialType::Interactive,
            'md', 'markdown' => MaterialType::Markdown,
            default => null,
        };
    }

    private function seedLessonMaterials(Lesson $lesson, array $files): void
    {
        // Skip if lesson already has materials
        if ($lesson->materials()->exists()) {
            return;
        }

        foreach ($files as $order => $fileData) {
            // Upload file to R2
            try {
                $publicUrl = $this->uploadToR2($fileData);
                $fileSize = filesize($fileData['path']) ?: 0;

                // Determine MIME type
                $mimeType = $this->getMimeType($fileData['path']);

                // Create material record
                LessonMaterial::create([
                    'id' => Str::uuid(),
                    'lesson_id' => $lesson->id,
                    'type' => $fileData['type'],
                    'title' => $lesson->title.' - '.str_replace(
                        '_',
                        ' ',
                        pathinfo($fileData['name'], PATHINFO_FILENAME)
                    ),
                    'description' => $this->generateDescription($fileData['type'], $fileData['name']),
                    'file_url' => $publicUrl,
                    'file_path' => $this->generateFilePath($fileData['type'], $fileData['name']),
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'order' => $order + 1,
                ]);
            } catch (\Exception $e) {
                $this->command->error("Failed to upload {$fileData['name']}: {$e->getMessage()}");
            }
        }
    }

    private function uploadToR2(array $fileData): string
    {
        $fileName = $fileData['name'];
        $filePath = $fileData['path'];
        $type = $fileData['type'];

        // Build S3 key with type-based folder
        $typeFolder = strtolower($type->value).'s';
        $timestamp = now()->format('YmdHis');
        $hash = substr(hash('sha256', $fileName.$timestamp), 0, 8);
        $key = "lessons/{$typeFolder}/{$hash}-{$fileName}";

        // Upload to R2
        $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => fopen($filePath, 'r'),
            'ContentType' => $this->getMimeType($filePath),
        ]);

        // Return public URL using custom domain or fallback to R2 domain
        if ($this->customDomain) {
            return "{$this->customDomain}/{$key}";
        }

        return "https://{$this->bucket}.{$this->accountId}.r2.cloudflarestorage.com/{$key}";
    }

    private function getMimeType(string $filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return 'application/octet-stream';
        }

        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
    }

    private function generateFilePath(MaterialType $type, string $filename): string
    {
        $fileName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'-'.substr(hash('sha256', uniqid()), 0, 8);
        $typeFolder = strtolower($type->value).'s';

        return "lessons/{$typeFolder}/{$fileName}.{$type->name}";
    }

    private function generateDescription(MaterialType $type, string $filename): string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        return match ($type) {
            MaterialType::Video => 'Watch this video lecture to understand the core concepts. Includes examples, demonstrations, and visual explanations of key topics.',
            MaterialType::PDF => 'Reference guide with detailed information, code examples, diagrams, and best practices. Keep this as a reference while studying.',
            MaterialType::Document => 'Comprehensive notes covering the lesson content. Read through carefully and take additional notes as needed.',
            MaterialType::Audio => 'Listen to this podcast episode featuring expert discussion, Q&A, and deep-dive explanations of key concepts.',
            MaterialType::Presentation => 'View the professional slide deck with animations and demonstrations. Use as a study guide for the main concepts.',
            MaterialType::Image => 'Study this diagram, chart, or visual representation showing system components, relationships, and architecture.',
            MaterialType::Interactive => 'Hands-on interactive sandbox for practicing concepts. Experiment with code and test your understanding in real-time.',
            MaterialType::Markdown => 'Read through this formatted guide with sections, code examples, and key information. Use keyboard shortcuts and the outline for quick navigation.',
        };
    }
}
