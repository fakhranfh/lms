<?php

namespace App\Console\Commands;

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\School;
use App\Services\LessonMaterialService;
use App\Services\R2StorageService;
use Illuminate\Console\Command;

class TestPresignedUpload extends Command
{
    protected $signature = 'r2:test-presigned {--lesson-id=}';

    protected $description = 'Test presigned POST URL flow (direct R2 upload)';

    public function handle(): int
    {
        $this->info('🧪 Testing Presigned POST Upload Flow...');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Validate R2 credentials
        if (! config('services.r2.access_key_id')) {
            $this->error('❌ R2 credentials not configured in .env');

            return self::FAILURE;
        }

        try {
            // Get or create lesson
            $lessonId = $this->option('lesson-id');
            if ($lessonId) {
                $lesson = Lesson::findOrFail($lessonId);
            } else {
                $lesson = Lesson::factory()->create();
                $this->line("📝 Created Lesson: {$lesson->title} ({$lesson->id})");
            }

            $this->line('');
            $this->info('📋 New Flow: Direct R2 Upload (Client-Side)');
            $this->line('');

            // Step 1: Create test file
            $this->line('Step 1️⃣  Creating test file locally...');
            $testFilePath = sys_get_temp_dir().'/test-presigned-'.time().'.pdf';
            file_put_contents($testFilePath, str_repeat('Test PDF Content ', 6400)); // ~100 KB
            $fileSize = filesize($testFilePath);
            $fileName = 'test-presigned-'.time().'.pdf';
            $this->info('✅ Test file created: '.$fileName.' ('.$fileSize.' bytes)');

            // Step 2: Test VALIDATION - try invalid extension first
            $this->line('');
            $this->line('Step 2️⃣  Testing file extension validation...');
            $this->line('');
            $service = app(LessonMaterialService::class);

            // Try invalid extension
            $this->line('  a) Trying INVALID extension (.exe for PDF)...');
            try {
                $invalidFile = 'malware.exe';
                $service->generatePresignedUploadUrl($lesson->id, $invalidFile, 'PDF');
                $this->error('    ❌ Should have rejected!');
            } catch (\InvalidArgumentException $e) {
                $this->info('    ✅ Rejected: '.$e->getMessage());
            }

            $this->line('');
            $this->line('  b) Trying VALID extension (.pdf for PDF)...');
            try {
                $presignedData = $service->generatePresignedUploadUrl($lesson->id, $fileName, 'PDF');
                $this->info('    ✅ Accepted! Presigned URL generated');
            } catch (\InvalidArgumentException $e) {
                $this->error('    ❌ Error: '.$e->getMessage());

                return self::FAILURE;
            }

            $this->line('');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Upload URL', substr($presignedData['url'], 0, 80).'...'],
                    ['S3 Key', $presignedData['key']],
                    ['Validated', '✅ Extension validated on server'],
                ]
            );

            // Step 3: Upload file directly to R2 using presigned URL
            $this->line('');
            $this->line('Step 3️⃣  Client uploads file directly to R2...');
            $this->line('        (Using presigned PUT URL, no server involved)');

            try {
                $fileContent = file_get_contents($testFilePath);
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $presignedData['url'],
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                    CURLOPT_POSTFIELDS => $fileContent,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/pdf',
                        'Content-Length: '.$fileSize,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $this->info('✅ File uploaded to R2 successfully!');
                    $fileUrl = 'https://'.$presignedData['key']; // Simplified URL construction
                } else {
                    throw new \Exception("Upload failed with HTTP {$httpCode}");
                }
            } catch (\Exception $e) {
                $this->error('Upload error: '.$e->getMessage());

                return self::FAILURE;
            }

            $bucket = config('services.r2.bucket');
            $accountId = config('services.r2.account_id');
            $fileUrl = 'https://'.$bucket.'.'.$accountId.'.r2.cloudflarestorage.com/'.$presignedData['key'];

            $this->line('');
            $this->line('File URL: '.$fileUrl);

            // Step 4: Client notifies server to save metadata
            $this->line('');
            $this->line('Step 4️⃣  Client notifies server with file URL...');
            $this->line('        Server verifies and saves metadata');
            $this->line('');

            // Verify file exists in R2
            $r2Service = app(R2StorageService::class);
            $fileInfo = $r2Service->verifyFileExists($fileUrl);

            if ($fileInfo['exists']) {
                $this->info('✅ File verified in R2!');
            } else {
                $this->warn('⚠️  File not yet verified in R2 (may take a moment)');
            }

            // Create material
            $material = LessonMaterial::create([
                'lesson_id' => $lesson->id,
                'type' => MaterialType::PDF,
                'title' => 'Presigned Upload Test',
                'description' => 'Uploaded via presigned PUT URL (direct to R2)',
                'file_url' => $fileUrl,
                'file_path' => $presignedData['key'],
                'file_size' => $fileSize,
                'mime_type' => 'application/pdf',
                'order' => 1,
            ]);

            $this->info('✅ Material metadata saved!');
            $this->line('');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Material ID', $material->id],
                    ['Title', $material->title],
                    ['Type', $material->type->value],
                    ['File Size', number_format($material->file_size).' bytes'],
                    ['File URL', $material->file_url],
                ]
            );

            // Show quota
            $this->line('');
            $r2Service = app(R2StorageService::class);
            $school = School::first();
            $quota = $r2Service->checkSchoolQuota($school->id);
            $this->table(
                ['Quota Metric', 'Value'],
                [
                    ['Used', number_format($quota['used']).' bytes'],
                    ['Total', number_format($quota['limit']).' bytes'],
                    ['Usage %', number_format($quota['percentage'], 2).'%'],
                ]
            );

            $this->line('');
            $this->info('✨ Presigned Upload Flow Summary:');
            $this->line('');
            $this->line('  1️⃣  Client requests presigned PUT URL (1 server call)');
            $this->line('  2️⃣  Client uploads file DIRECTLY to R2 (via presigned URL)');
            $this->line('  3️⃣  Client notifies server with file URL');
            $this->line('  4️⃣  Server verifies file exists and saves metadata');
            $this->line('');
            $this->warn('💡 Benefit: No server storage needed, direct R2 upload, lower latency!');

            // Cleanup
            @unlink($testFilePath);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("\n❌ Error: {$e->getMessage()}");
            @unlink($testFilePath ?? null);

            return self::FAILURE;
        }
    }
}
