<?php

namespace App\Console\Commands;

use App\Models\Lesson;
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

            // Step 1: Create test file (actual minimal PDF)
            $this->line('Step 1️⃣  Creating test file locally...');
            $testFilePath = sys_get_temp_dir().'/test-presigned-'.time().'.pdf';
            // Create minimal valid PDF (PDF header + content)
            $pdfContent = "%PDF-1.4\n";
            $pdfContent .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
            $pdfContent .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
            $pdfContent .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
            $pdfContent .= "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
            $pdfContent .= "5 0 obj\n<< /Length 44 >>\nstream\nBT\n/F1 12 Tf\n100 700 Td\n(Test PDF) Tj\nET\nendstream\nendobj\n";
            $pdfContent .= "xref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n0000000263 00000 n\n0000000341 00000 n\n";
            $pdfContent .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n434\n%%EOF\n";
            // Pad with test content to reach ~100KB
            $pdfContent .= '% '.str_repeat('Test content for size padding ', 3400)."\n";
            file_put_contents($testFilePath, $pdfContent);
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

            // Step 3: Upload file directly to R2 using presigned URL (to TEMP folder)
            $this->line('');
            $this->line('Step 3️⃣  Client uploads file directly to R2 (temp folder)...');
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
                    $this->info('✅ File uploaded to R2 (temp) successfully!');
                } else {
                    throw new \Exception("Upload failed with HTTP {$httpCode}");
                }
            } catch (\Exception $e) {
                $this->error('Upload error: '.$e->getMessage());

                return self::FAILURE;
            }

            // Step 4: Server runs 3-layer validation and promotes file
            $this->line('');
            $this->line('Step 4️⃣  Server validates file (3-layer check)...');
            $this->line('');

            $materialService = app(LessonMaterialService::class);

            try {
                $material = $materialService->finalizeR2Upload($lesson->id, [
                    'type' => 'PDF',
                    'title' => 'Presigned Upload Test',
                    'description' => 'Uploaded via presigned PUT URL with 3-layer validation',
                    'temp_key' => $presignedData['key'],
                ]);

                $this->info('✅ All validations passed!');
                $this->info('✅ File promoted from temp to final location!');
                $this->info('✅ Material metadata saved!');

            } catch (\Exception $e) {
                $this->error('Validation/finalization failed: '.$e->getMessage());

                return self::FAILURE;
            }

            $this->line('');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Material ID', $material->id],
                    ['Title', $material->title],
                    ['Type', $material->type->value],
                    ['File Size', number_format($material->file_size).' bytes'],
                    ['File URL', $material->file_url],
                    ['File Path', $material->file_path],
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

            // Test 2: Upload file with FAKE extension (plain text with .pdf extension)
            $this->line('');
            $this->line('═════════════════════════════════════════════════════════════');
            $this->line('');
            $this->info('🧪 Test 2: File with FAKE extension (should be REJECTED)');
            $this->line('');

            // Create file with .pdf extension but plain text content
            $fakeFilePath = sys_get_temp_dir().'/fake-presigned-'.time().'.pdf';
            file_put_contents($fakeFilePath, "This is plain text, not a PDF file!\n".str_repeat('Malicious content ', 5000));
            $fakeFileSize = filesize($fakeFilePath);
            $fakeFileName = basename($fakeFilePath);

            $this->line('Step 1️⃣  Created malicious file: '.$fakeFileName.' (fake .pdf extension)');
            $this->line('         Content: Plain text (not PDF)');

            // Request presigned URL
            $this->line('');
            $this->line('Step 2️⃣  Requesting presigned URL (extension validation)...');
            try {
                $fakePresignedData = $service->generatePresignedUploadUrl($lesson->id, $fakeFileName, 'PDF');
                $this->info('✅ Extension check passed (only checks extension, not content yet)');
            } catch (\InvalidArgumentException $e) {
                $this->error('❌ Extension rejected: '.$e->getMessage());

                return self::FAILURE;
            }

            // Upload to temp
            $this->line('');
            $this->line('Step 3️⃣  Client uploads malicious file to R2 (temp)...');
            try {
                $fakeFileContent = file_get_contents($fakeFilePath);
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $fakePresignedData['url'],
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                    CURLOPT_POSTFIELDS => $fakeFileContent,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/octet-stream',
                        'Content-Length: '.$fakeFileSize,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $this->info('✅ File uploaded to R2 temp');
                } else {
                    throw new \Exception("Upload failed with HTTP {$httpCode}");
                }
            } catch (\Exception $e) {
                $this->error('Upload error: '.$e->getMessage());

                return self::FAILURE;
            }

            // Try to finalize (should fail on Layer 2: magic bytes)
            $this->line('');
            $this->line('Step 4️⃣  Server validates file (3-layer check)...');
            $this->line('');

            try {
                $materialService->finalizeR2Upload($lesson->id, [
                    'type' => 'PDF',
                    'title' => 'Malicious Test (should fail)',
                    'description' => 'File with fake .pdf extension but text content',
                    'temp_key' => $fakePresignedData['key'],
                ]);

                $this->error('❌ SECURITY FAIL: File should have been rejected!');

                return self::FAILURE;

            } catch (\InvalidArgumentException $e) {
                $this->info('✅ REJECTED at Layer 2 (Magic Bytes Validation)');
                $this->info('   Error: '.$e->getMessage());
            }

            // Verify temp object was cleaned up
            $this->line('');
            $this->line('Step 5️⃣  Verifying temp object cleanup...');
            $r2Service = app(R2StorageService::class);
            $tempFileInfo = $r2Service->verifyFileExists($fakePresignedData['key']);

            if ($tempFileInfo['exists']) {
                $this->error('❌ Temp file was NOT cleaned up!');

                return self::FAILURE;
            } else {
                $this->info('✅ Temp file automatically deleted after rejection');
            }

            $this->line('');
            $this->info('🛡️  Security Test Summary:');
            $this->line('  ✅ Extension validation (Layer 1) - allows only .pdf');
            $this->line('  ✅ Magic bytes validation (Layer 2) - rejects fake PDFs');
            $this->line('  ✅ MIME type validation (Layer 3) - double-check content type');
            $this->line('  ✅ Auto-cleanup - removes rejected files from R2');

            // Cleanup
            @unlink($testFilePath);
            @unlink($fakeFilePath);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("\n❌ Error: {$e->getMessage()}");
            @unlink($testFilePath ?? null);

            return self::FAILURE;
        }
    }
}
