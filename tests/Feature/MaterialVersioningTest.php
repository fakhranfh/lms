<?php

use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Models\School;
use App\Repositories\Lesson\LessonRepository;
use App\Repositories\LessonMaterial\LessonMaterialRepository;
use App\Repositories\LessonMaterialUser\LessonMaterialUserRepository;
use App\Services\LessonMaterialService;
use App\Services\R2StorageService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->school = School::factory()->create();
    $this->course = Course::factory()->for($this->school)->create();
    $this->module = Module::factory()->for($this->course)->create();
    $this->lesson = Lesson::factory()->for($this->module)->create();
});

test('material version is 1 on creation', function () {
    $material = LessonMaterial::factory()->for($this->lesson)->create();

    expect($material->version)->toBe(1);
    expect($material->is_active)->toBe(true);
});

test('updating material with file creates new version', function () {
    // Mock R2StorageService to avoid actual S3 calls
    $mockR2Service = Mockery::mock(R2StorageService::class);
    $mockR2Service->shouldReceive('upload')
        ->once()
        ->andReturn('https://r2.example.com/lessons/'.$this->lesson->id.'/materials/test.pdf');
    $mockR2Service->shouldReceive('extractKeyFromPath')
        ->once()
        ->andReturn('lessons/'.$this->lesson->id.'/materials/test.pdf');

    $service = new LessonMaterialService(
        app(LessonRepository::class),
        app(LessonMaterialRepository::class),
        app(LessonMaterialUserRepository::class),
        $mockR2Service,
    );

    $material = LessonMaterial::factory()
        ->for($this->lesson)
        ->withType(MaterialType::PDF)
        ->create([
            'title' => 'Test Material',
            'version' => 1,
            'is_active' => true,
        ]);

    // Create a fake file for upload
    $file = UploadedFile::fake()->create('updated.pdf', 100);

    // Update with file should create new version
    $updated = $service->update($material->id, [
        'file' => $file,
        'title' => 'Test Material',
    ]);

    expect($updated->version)->toBe(2);
    expect($updated->is_active)->toBe(true);

    // Original version should now be inactive
    $original = LessonMaterial::find($material->id);
    expect($original->is_active)->toBe(false);

    // Both versions should exist
    $allVersions = LessonMaterial::where('lesson_id', $this->lesson->id)
        ->where('title', 'Test Material')
        ->get();
    expect($allVersions)->toHaveCount(2);
});

test('updating material without file updates metadata inline', function () {
    $service = app(LessonMaterialService::class);

    $material = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Original Title',
            'description' => 'Original Description',
        ]);

    $updated = $service->update($material->id, [
        'title' => 'Updated Title',
        'description' => 'Updated Description',
    ]);

    expect($updated->version)->toBe(1);
    expect($updated->title)->toBe('Updated Title');
    expect($updated->description)->toBe('Updated Description');
});

test('can retrieve all versions of a material', function () {
    $service = app(LessonMaterialService::class);

    $material = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Versioned Material',
            'version' => 1,
            'is_active' => true,
        ]);

    // Simulate creating more versions by manually adding them
    LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Versioned Material',
            'version' => 2,
            'is_active' => false,
        ]);

    LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Versioned Material',
            'version' => 3,
            'is_active' => false,
        ]);

    $versions = $service->getAllVersions($material->id);

    expect($versions)->toHaveCount(3);
    expect($versions->first()->version)->toBe(3);
    expect($versions->last()->version)->toBe(1);
});

test('can switch to different version', function () {
    $service = app(LessonMaterialService::class);

    $v1 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Switch Material',
            'version' => 1,
            'is_active' => true,
        ]);

    $v2 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Switch Material',
            'version' => 2,
            'is_active' => false,
        ]);

    // Switch to version 2
    $activated = $service->switchVersion($v1->id, 2);

    expect($activated->version)->toBe(2);
    expect($activated->is_active)->toBe(true);

    // V1 should now be inactive
    expect($v1->fresh()->is_active)->toBe(false);
});

test('cannot delete the only version of a material', function () {
    $service = app(LessonMaterialService::class);

    $material = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Only Version',
            'version' => 1,
            'is_active' => true,
        ]);

    expect(fn () => $service->deleteVersion($material->id, 1))
        ->toThrow(InvalidArgumentException::class, 'Cannot delete the last version');
});

test('can delete version when multiple exist', function () {
    $service = app(LessonMaterialService::class);

    $v1 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Multi Version',
            'version' => 1,
            'is_active' => false,
        ]);

    $v2 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Multi Version',
            'version' => 2,
            'is_active' => true,
        ]);

    // Delete version 1
    $service->deleteVersion($v1->id, 1);

    // V1 should be gone
    expect(LessonMaterial::find($v1->id))->toBeNull();

    // V2 should still exist and be active
    expect($v2->fresh()->is_active)->toBe(true);
});

test('deleting active version activates previous version', function () {
    $service = app(LessonMaterialService::class);

    $v1 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Active Delete Test',
            'version' => 1,
            'is_active' => false,
        ]);

    $v2 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Active Delete Test',
            'version' => 2,
            'is_active' => true,
        ]);

    // Delete version 2 (active)
    $service->deleteVersion($v2->id, 2);

    // V1 should now be active
    expect($v1->fresh()->is_active)->toBe(true);
});

test('material scope filters only active versions', function () {
    $v1 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Active Filter Test',
            'version' => 1,
            'is_active' => false,
        ]);

    $v2 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Active Filter Test',
            'version' => 2,
            'is_active' => true,
        ]);

    $active = LessonMaterial::active()->where('lesson_id', $this->lesson->id)->get();

    expect($active)->toHaveCount(1);
    expect($active->first()->id)->toBe($v2->id);
});

test('material getAllVersions returns all versions ordered by version desc', function () {
    $material = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'All Versions Test',
            'version' => 1,
            'is_active' => false,
        ]);

    LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'All Versions Test',
            'version' => 2,
            'is_active' => false,
        ]);

    LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'All Versions Test',
            'version' => 3,
            'is_active' => true,
        ]);

    $versions = $material->getAllVersions();

    expect($versions)->toHaveCount(3);
    expect($versions->pluck('version')->toArray())->toBe([3, 2, 1]);
});

test('material getActiveVersion returns current active version', function () {
    $v1 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Active Version Test',
            'version' => 1,
            'is_active' => false,
        ]);

    $v2 = LessonMaterial::factory()
        ->for($this->lesson)
        ->create([
            'title' => 'Active Version Test',
            'version' => 2,
            'is_active' => true,
        ]);

    $activeVersion = $v1->getActiveVersion();

    expect($activeVersion->id)->toBe($v2->id);
    expect($activeVersion->version)->toBe(2);
});

test('finalizeVersionUpload creates new version from presigned upload flow', function () {
    $material = LessonMaterial::factory()
        ->for($this->lesson)
        ->withType(MaterialType::PDF)
        ->create([
            'title' => 'Presigned Upload Test',
            'version' => 1,
            'is_active' => true,
        ]);

    $mockR2Service = Mockery::mock(R2StorageService::class);
    $mockR2Service->shouldReceive('verifyFileExists')
        ->once()
        ->with('temp/abc/replacement.pdf')
        ->andReturn(['exists' => true, 'size' => 2048, 'mime_type' => 'application/pdf']);
    $mockR2Service->shouldReceive('downloadToLocalTemp')
        ->once()
        ->andReturn(sys_get_temp_dir().'/fake-download.pdf');
    $mockR2Service->shouldReceive('validateFileContent')->once();
    $mockR2Service->shouldReceive('validateMimeType')->once();
    $mockR2Service->shouldReceive('schoolPrefix')->once()->andReturn('');
    $mockR2Service->shouldReceive('promoteFromTemp')->once();
    $mockR2Service->shouldReceive('getPublicUrl')
        ->once()
        ->andReturn('https://r2.example.com/lessons/'.$this->lesson->id.'/materials/replacement.pdf');

    // Create the fake local temp file so file_exists()/unlink() in finally block don't error
    file_put_contents(sys_get_temp_dir().'/fake-download.pdf', '%PDF-1.4 fake content');

    $service = new LessonMaterialService(
        app(LessonRepository::class),
        app(LessonMaterialRepository::class),
        app(LessonMaterialUserRepository::class),
        $mockR2Service,
    );

    $newVersion = $service->finalizeVersionUpload($material->id, [
        'temp_key' => 'temp/abc/replacement.pdf',
    ]);

    expect($newVersion->version)->toBe(2);
    expect($newVersion->is_active)->toBe(true);
    expect($newVersion->title)->toBe('Presigned Upload Test');

    $original = LessonMaterial::find($material->id);
    expect($original->is_active)->toBe(false);

    $allVersions = LessonMaterial::where('lesson_id', $this->lesson->id)
        ->where('title', 'Presigned Upload Test')
        ->get();
    expect($allVersions)->toHaveCount(2);
});

test('finalizeVersionUpload throws when temp file not found', function () {
    $material = LessonMaterial::factory()
        ->for($this->lesson)
        ->withType(MaterialType::PDF)
        ->create(['title' => 'Missing Temp File Test']);

    $mockR2Service = Mockery::mock(R2StorageService::class);
    $mockR2Service->shouldReceive('verifyFileExists')
        ->once()
        ->andReturn(['exists' => false]);

    $service = new LessonMaterialService(
        app(LessonRepository::class),
        app(LessonMaterialRepository::class),
        app(LessonMaterialUserRepository::class),
        $mockR2Service,
    );

    expect(fn () => $service->finalizeVersionUpload($material->id, ['temp_key' => 'temp/missing.pdf']))
        ->toThrow(Exception::class, 'Temp file not found in R2');
});
