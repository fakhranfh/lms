<?php

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Repositories\LessonMaterial\LessonMaterialRepositoryInterface;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->repository = app(LessonMaterialRepositoryInterface::class);
});

test('can get all materials for a lesson ordered by order column', function () {
    $lesson = Lesson::factory()->create();

    LessonMaterial::factory()
        ->for($lesson)
        ->create(['order' => 3, 'type' => MaterialType::Video]);

    LessonMaterial::factory()
        ->for($lesson)
        ->create(['order' => 1, 'type' => MaterialType::PDF]);

    LessonMaterial::factory()
        ->for($lesson)
        ->create(['order' => 2, 'type' => MaterialType::Audio]);

    $materials = $this->repository->getByLesson($lesson->id);

    expect($materials)->toHaveCount(3);
    expect($materials[0]->order)->toBe(1);
    expect($materials[1]->order)->toBe(2);
    expect($materials[2]->order)->toBe(3);
});

test('can filter materials by type', function () {
    $lesson = Lesson::factory()->create();

    LessonMaterial::factory(2)->for($lesson)->create(['type' => MaterialType::Video]);
    LessonMaterial::factory(1)->for($lesson)->create(['type' => MaterialType::PDF]);

    $videoMaterials = $this->repository->getByLessonAndType($lesson->id, MaterialType::Video);

    expect($videoMaterials)->toHaveCount(2);
    expect($videoMaterials[0]->type)->toBe(MaterialType::Video);
    expect($videoMaterials[1]->type)->toBe(MaterialType::Video);
});

test('can find a material by id', function () {
    $lesson = Lesson::factory()->create();
    $material = LessonMaterial::factory()->for($lesson)->create();

    $found = $this->repository->find($material->id);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($material->id);
    expect($found->title)->toBe($material->title);
});

test('returns null when finding non-existent material', function () {
    $uuid = Str::uuid();
    $found = $this->repository->find($uuid->toString());

    expect($found)->toBeNull();
});

test('can create a new material', function () {
    $lesson = Lesson::factory()->create();

    $material = $this->repository->create([
        'lesson_id' => $lesson->id,
        'type' => MaterialType::Video,
        'title' => 'Test Video',
        'description' => 'Test Description',
        'file_url' => 'https://example.com/video.mp4',
        'file_path' => 'videos/test.mp4',
        'file_size' => 1024000,
        'mime_type' => 'video/mp4',
    ]);

    expect($material->lesson_id)->toBe($lesson->id);
    expect($material->type)->toBe(MaterialType::Video);
    expect($material->title)->toBe('Test Video');
    expect($material->file_size)->toBe(1024000);
});

test('material order auto-increments on create without explicit order', function () {
    $lesson = Lesson::factory()->create();

    $material1 = $this->repository->create([
        'lesson_id' => $lesson->id,
        'type' => MaterialType::Video,
        'title' => 'Material 1',
        'file_url' => 'https://example.com/1.mp4',
        'file_size' => 1000,
        'mime_type' => 'video/mp4',
    ]);

    $material2 = $this->repository->create([
        'lesson_id' => $lesson->id,
        'type' => MaterialType::PDF,
        'title' => 'Material 2',
        'file_url' => 'https://example.com/2.pdf',
        'file_size' => 500,
        'mime_type' => 'application/pdf',
    ]);

    expect($material1->order)->toBe(1);
    expect($material2->order)->toBe(2);
});

test('can update a material', function () {
    $lesson = Lesson::factory()->create();
    $material = LessonMaterial::factory()->for($lesson)->create(['title' => 'Old Title']);

    $updated = $this->repository->update($material->id, [
        'title' => 'New Title',
        'description' => 'New Description',
    ]);

    expect($updated->title)->toBe('New Title');
    expect($updated->description)->toBe('New Description');
});

test('can delete a material', function () {
    $lesson = Lesson::factory()->create();
    $material = LessonMaterial::factory()->for($lesson)->create();

    $this->repository->delete($material->id);

    expect(LessonMaterial::find($material->id))->toBeNull();
});

test('can get next order for a lesson', function () {
    $lesson = Lesson::factory()->create();

    LessonMaterial::factory(2)->for($lesson)->create();

    $nextOrder = $this->repository->getNextOrder($lesson->id);

    expect($nextOrder)->toBe(3);
});

test('next order is 1 for empty lesson', function () {
    $lesson = Lesson::factory()->create();

    $nextOrder = $this->repository->getNextOrder($lesson->id);

    expect($nextOrder)->toBe(1);
});

test('can reorder materials', function () {
    $lesson = Lesson::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson)->create();
    $material2 = LessonMaterial::factory()->for($lesson)->create();
    $material3 = LessonMaterial::factory()->for($lesson)->create();

    // Reset orders to avoid collision during reorder
    $material1->update(['order' => 1]);
    $material2->update(['order' => 2]);
    $material3->update(['order' => 3]);

    $this->repository->reorder($lesson->id, [
        $material1->id => 3,
        $material2->id => 1,
        $material3->id => 2,
    ]);

    $material1->refresh();
    $material2->refresh();
    $material3->refresh();

    expect($material1->order)->toBe(3);
    expect($material2->order)->toBe(1);
    expect($material3->order)->toBe(2);
});

test('can get materials with eager-loaded relations', function () {
    $lesson = Lesson::factory()->create();
    $material = LessonMaterial::factory()->for($lesson)->create();

    $found = $this->repository->find($material->id, ['lesson']);

    expect($found->lesson)->not->toBeNull();
    expect($found->lesson->id)->toBe($lesson->id);
});

test('can get all materials with filters', function () {
    $lesson1 = Lesson::factory()->create();
    $lesson2 = Lesson::factory()->create();

    LessonMaterial::factory(2)->for($lesson1)->create(['type' => MaterialType::Video]);
    LessonMaterial::factory(3)->for($lesson2)->create(['type' => MaterialType::PDF]);

    $materials = $this->repository->get(['lesson_id' => $lesson1->id]);

    expect($materials)->toHaveCount(2);
    expect($materials[0]->lesson_id)->toBe($lesson1->id);
});

test('reorder only affects specified lesson', function () {
    $lesson1 = Lesson::factory()->create();
    $lesson2 = Lesson::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson1)->create(['order' => 1]);
    $material2 = LessonMaterial::factory()->for($lesson2)->create(['order' => 1]);

    $this->repository->reorder($lesson1->id, [
        $material1->id => 2,
    ]);

    $material1->refresh();
    $material2->refresh();

    expect($material1->order)->toBe(2);
    expect($material2->order)->toBe(1);
});
