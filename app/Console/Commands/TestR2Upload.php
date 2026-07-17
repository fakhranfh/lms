<?php

namespace App\Console\Commands;

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\School;
use App\Services\LessonMaterialService;
use App\Services\R2StorageService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

class TestR2Upload extends Command
{
    protected $signature = 'r2:test-upload {--lesson-id=} {--school-id=}';

    protected $description = 'Test upload a file to R2 and keep it (for manual testing)';

    public function handle(): int
    {
        $this->info('🧪 Testing R2 Upload...');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Validate R2 credentials
        if (! config('services.r2.access_key_id')) {
            $this->error('❌ R2 credentials not configured in .env');

            return self::FAILURE;
        }

        try {
            // Get or create school & lesson
            $schoolId = $this->option('school-id');
            $lessonId = $this->option('lesson-id');

            if ($schoolId) {
                $school = School::findOrFail($schoolId);
            } else {
                $school = School::first() ?? School::factory()->create();
                $this->line("📍 Using School: {$school->name} ({$school->id})");
            }

            if ($lessonId) {
                $lesson = Lesson::findOrFail($lessonId);
            } else {
                $lesson = Lesson::factory()->create();
                $this->line("📝 Created Lesson: {$lesson->title} ({$lesson->id})");
            }

            // Create fake file (100 KB)
            $fakeFile = UploadedFile::fake()->create('test-document-'.now()->timestamp.'.pdf', 100);
            $this->line("\n📁 File to upload: {$fakeFile->getClientOriginalName()} (100 KB)");

            // Upload via service
            $this->line('⏳ Uploading to R2...');
            $service = app(LessonMaterialService::class);
            $material = $service->create($lesson->id, [
                'type' => MaterialType::PDF->value,
                'title' => 'Test PDF - '.now()->format('Y-m-d H:i:s'),
                'description' => 'Manual test upload (do not delete until instructed)',
                'file' => $fakeFile,
            ]);

            // Success!
            $this->info("\n✅ Upload successful!");
            $this->line('');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Material ID', $material->id],
                    ['Lesson ID', $material->lesson_id],
                    ['Title', $material->title],
                    ['Type', $material->type->value],
                    ['File Size', number_format($material->file_size).' bytes'],
                    ['Order', $material->order],
                    ['Created At', $material->created_at],
                ]
            );

            $this->line('');
            $this->info('📍 File URL:');
            $this->line($material->file_url);

            // Show quota
            $this->line('');
            $r2Service = app(R2StorageService::class);
            $quota = $r2Service->checkSchoolQuota($school->id);
            $this->table(
                ['Quota Metric', 'Value'],
                [
                    ['Used', number_format($quota['used']).' bytes'],
                    ['Total', number_format($quota['limit']).' bytes'],
                    ['Remaining', number_format($quota['remaining']).' bytes'],
                    ['Usage %', number_format($quota['percentage'], 2).'%'],
                ]
            );

            $this->line('');
            $this->info('💾 File is now stored in R2 and will NOT be deleted.');
            $this->line('');
            $this->warn('📋 To delete this file later, run:');
            $this->line("   php artisan r2:delete-material {$material->id}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("\n❌ Upload failed: {$e->getMessage()}");
            $this->line('');

            if (str_contains($e->getMessage(), 'quota')) {
                $this->warn('Issue: R2 quota exceeded or not available');
            } elseif (str_contains($e->getMessage(), 'credentials')) {
                $this->warn('Issue: R2 credentials not configured');
                $this->info('Fix: Check .env has CLOUDFLARE_R2_* variables');
            } elseif (str_contains($e->getMessage(), 'File size')) {
                $this->warn('Issue: File size exceeds limit for this type');
            } elseif (str_contains($e->getMessage(), 'extension')) {
                $this->warn('Issue: File extension not allowed for this type');
            } else {
                $this->warn('Issue: '.get_class($e));
            }

            return self::FAILURE;
        }
    }
}
