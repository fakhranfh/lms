<?php

use App\Enums\MaterialType;
use App\Models\Assessment;
use App\Models\ExamReferenceFile;
use App\Models\User;
use App\Services\ExamReferenceFileService;
use App\Services\R2StorageService;

describe('ExamReferenceFileService', function () {
    test('service can be instantiated', function () {
        expect(app(ExamReferenceFileService::class))->toBeInstanceOf(ExamReferenceFileService::class);
    });

    test('forAssessmentAndUser returns only that user\'s files for the assessment', function () {
        $assessment = Assessment::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ExamReferenceFile::factory()->for($assessment)->for($user)->count(2)->create();
        ExamReferenceFile::factory()->for($assessment)->for($otherUser)->create();

        $service = app(ExamReferenceFileService::class);
        $files = $service->forAssessmentAndUser($assessment->id, $user->id);

        expect($files)->toHaveCount(2)
            ->and($files->every(fn (ExamReferenceFile $file) => $file->user_id === $user->id))->toBeTrue();
    });

    test('finalize upload rejects an invalid material type', function () {
        $service = app(ExamReferenceFileService::class);

        expect(fn () => $service->finalizeUpload('assessment-id', 'user-id', ['type' => 'NotAType', 'temp_key' => 'temp/exam-reference/foo.pdf']))
            ->toThrow(InvalidArgumentException::class);
    });

    test('finalize upload requires a temp key', function () {
        $service = app(ExamReferenceFileService::class);

        expect(fn () => $service->finalizeUpload('assessment-id', 'user-id', ['type' => 'PDF']))
            ->toThrow(InvalidArgumentException::class, 'temp_key is required');
    });

    test('finalize upload validates content, promotes the file, and persists the record', function () {
        $assessment = Assessment::factory()->create();
        $user = User::factory()->create();
        $tempKey = 'schools/demo/temp/exam-reference/abc123-notes.pdf';

        $r2Mock = Mockery::mock(R2StorageService::class);
        $r2Mock->shouldReceive('verifyFileExists')->once()->with($tempKey)->andReturn(['exists' => true, 'size' => 1024, 'mime_type' => 'application/pdf']);
        $r2Mock->shouldReceive('downloadToLocalTemp')->once()->with($tempKey)->andReturn(sys_get_temp_dir().'/fake-notes.pdf');
        $r2Mock->shouldReceive('validateFileContent')->once();
        $r2Mock->shouldReceive('validateMimeType')->once();
        $r2Mock->shouldReceive('schoolPrefix')->once()->andReturn('schools/demo/');
        $r2Mock->shouldReceive('promoteFromTemp')->once();

        $this->app->instance(R2StorageService::class, $r2Mock);

        $service = app(ExamReferenceFileService::class);
        $file = $service->finalizeUpload($assessment->id, $user->id, [
            'type' => MaterialType::PDF->value,
            'temp_key' => $tempKey,
            'title' => 'notes.pdf',
        ]);

        expect($file)->toBeInstanceOf(ExamReferenceFile::class)
            ->and($file->assessment_id)->toBe($assessment->id)
            ->and($file->user_id)->toBe($user->id)
            ->and($file->type)->toBe(MaterialType::PDF)
            ->and($file->title)->toBe('notes.pdf');

        $this->assertDatabaseHas('exam_reference_files', [
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
        ]);
    });

    test('delete removes only the owner\'s file', function () {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $file = ExamReferenceFile::factory()->for($owner)->create(['file_path' => 'schools/demo/exam-reference/notes.pdf']);

        $r2Mock = Mockery::mock(R2StorageService::class);
        $r2Mock->shouldNotReceive('delete');
        $this->app->instance(R2StorageService::class, $r2Mock);

        $service = app(ExamReferenceFileService::class);
        $deleted = $service->delete($file->id, $intruder->id);

        expect($deleted)->toBe(0);
        $this->assertDatabaseHas('exam_reference_files', ['id' => $file->id]);
    });

    test('delete removes the file and its R2 object for the owner', function () {
        $owner = User::factory()->create();
        $file = ExamReferenceFile::factory()->for($owner)->create(['file_path' => 'schools/demo/exam-reference/notes.pdf']);

        $r2Mock = Mockery::mock(R2StorageService::class);
        $r2Mock->shouldReceive('delete')->once()->with('schools/demo/exam-reference/notes.pdf');
        $this->app->instance(R2StorageService::class, $r2Mock);

        $service = app(ExamReferenceFileService::class);
        $deleted = $service->delete($file->id, $owner->id);

        expect($deleted)->toBe(1);
        $this->assertDatabaseMissing('exam_reference_files', ['id' => $file->id]);
    });
});
