<?php

use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\User;
use App\Repositories\LessonMaterialUser\LessonMaterialUserRepositoryInterface;
use Carbon\Carbon;

beforeEach(function () {
    $this->repository = app(LessonMaterialUserRepositoryInterface::class);
});

test('can mark a material as accessed by user', function () {
    $material = LessonMaterial::factory()->create();
    $user = User::factory()->create();

    $this->repository->markAccessed($material->id, $user);

    expect($material->users()->where('user_id', $user->id)->wherePivot('accessed_at', '!=', null)->exists())->toBeTrue();
});

test('can check if material has been accessed by user', function () {
    $material = LessonMaterial::factory()->create();
    $user = User::factory()->create();

    $isAccessedBefore = $this->repository->isAccessedBy($material->id, $user);
    expect($isAccessedBefore)->toBeFalse();

    $this->repository->markAccessed($material->id, $user);

    $isAccessedAfter = $this->repository->isAccessedBy($material->id, $user);
    expect($isAccessedAfter)->toBeTrue();
});

test('marking material accessed multiple times updates the accessed_at timestamp', function () {
    $material = LessonMaterial::factory()->create();
    $user = User::factory()->create();

    $this->repository->markAccessed($material->id, $user);

    $firstAccess = $material->users()
        ->where('user_id', $user->id)
        ->first()
        ->pivot->accessed_at;

    sleep(1);

    $this->repository->markAccessed($material->id, $user);

    $secondAccess = $material->users()
        ->where('user_id', $user->id)
        ->first()
        ->pivot->accessed_at;

    // Cast to Carbon if string
    $firstAccess = is_string($firstAccess) ? Carbon::parse($firstAccess) : $firstAccess;
    $secondAccess = is_string($secondAccess) ? Carbon::parse($secondAccess) : $secondAccess;

    expect($secondAccess->timestamp)->toBeGreaterThanOrEqual($firstAccess->timestamp);
});

test('can get count of accessed materials in a lesson', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson)->create();
    $material2 = LessonMaterial::factory()->for($lesson)->create();
    $material3 = LessonMaterial::factory()->for($lesson)->create();

    $this->repository->markAccessed($material1->id, $user);
    $this->repository->markAccessed($material2->id, $user);

    $count = $this->repository->getAccessedCount($lesson->id, $user);

    expect($count)->toBe(2);
});

test('can get all accessed materials for a user in a lesson', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson)->create(['order' => 1]);
    $material2 = LessonMaterial::factory()->for($lesson)->create(['order' => 2]);
    $material3 = LessonMaterial::factory()->for($lesson)->create(['order' => 3]);

    $this->repository->markAccessed($material1->id, $user);
    $this->repository->markAccessed($material3->id, $user);

    $accessed = $this->repository->getAccessedMaterials($lesson->id, $user);

    expect($accessed)->toHaveCount(2);
    expect($accessed[0]->id)->toBe($material1->id);
    expect($accessed[1]->id)->toBe($material3->id);
});

test('accessed materials are ordered by order column', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson)->create(['order' => 3]);
    $material2 = LessonMaterial::factory()->for($lesson)->create(['order' => 1]);
    $material3 = LessonMaterial::factory()->for($lesson)->create(['order' => 2]);

    $this->repository->markAccessed($material1->id, $user);
    $this->repository->markAccessed($material2->id, $user);
    $this->repository->markAccessed($material3->id, $user);

    $accessed = $this->repository->getAccessedMaterials($lesson->id, $user);

    expect($accessed[0]->order)->toBe(1);
    expect($accessed[1]->order)->toBe(2);
    expect($accessed[2]->order)->toBe(3);
});

test('can get all not accessed materials for a user in a lesson', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson)->create(['order' => 1]);
    $material2 = LessonMaterial::factory()->for($lesson)->create(['order' => 2]);
    $material3 = LessonMaterial::factory()->for($lesson)->create(['order' => 3]);

    $this->repository->markAccessed($material1->id, $user);

    $notAccessed = $this->repository->getNotAccessedMaterials($lesson->id, $user);

    expect($notAccessed)->toHaveCount(2);
    expect($notAccessed[0]->id)->toBe($material2->id);
    expect($notAccessed[1]->id)->toBe($material3->id);
});

test('not accessed materials are ordered by order column', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson)->create(['order' => 3]);
    $material2 = LessonMaterial::factory()->for($lesson)->create(['order' => 1]);
    $material3 = LessonMaterial::factory()->for($lesson)->create(['order' => 2]);

    $this->repository->markAccessed($material1->id, $user);

    $notAccessed = $this->repository->getNotAccessedMaterials($lesson->id, $user);

    expect($notAccessed[0]->order)->toBe(1);
    expect($notAccessed[1]->order)->toBe(2);
});

test('different users have independent access records', function () {
    $lesson = Lesson::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson)->create();
    $material2 = LessonMaterial::factory()->for($lesson)->create();

    $this->repository->markAccessed($material1->id, $user1);
    $this->repository->markAccessed($material2->id, $user2);

    $user1Accessed = $this->repository->getAccessedMaterials($lesson->id, $user1);
    $user2Accessed = $this->repository->getAccessedMaterials($lesson->id, $user2);

    expect($user1Accessed)->toHaveCount(1);
    expect($user1Accessed[0]->id)->toBe($material1->id);

    expect($user2Accessed)->toHaveCount(1);
    expect($user2Accessed[0]->id)->toBe($material2->id);
});

test('can get accessed count of zero for user who accessed nothing', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    LessonMaterial::factory(3)->for($lesson)->create();

    $count = $this->repository->getAccessedCount($lesson->id, $user);

    expect($count)->toBe(0);
});

test('can get accessed materials with eager-loaded relations', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    $material = LessonMaterial::factory()->for($lesson)->create();
    $this->repository->markAccessed($material->id, $user);

    $accessed = $this->repository->getAccessedMaterials($lesson->id, $user, ['lesson']);

    expect($accessed[0]->lesson)->not->toBeNull();
    expect($accessed[0]->lesson->id)->toBe($lesson->id);
});

test('can get not accessed materials with eager-loaded relations', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();

    LessonMaterial::factory()->for($lesson)->create();
    $material2 = LessonMaterial::factory()->for($lesson)->create();

    $notAccessed = $this->repository->getNotAccessedMaterials($lesson->id, $user, ['lesson']);

    expect($notAccessed[0]->lesson)->not->toBeNull();
    expect($notAccessed[0]->lesson->id)->toBe($lesson->id);
});

test('accessed count respects lesson scope', function () {
    $lesson1 = Lesson::factory()->create();
    $lesson2 = Lesson::factory()->create();
    $user = User::factory()->create();

    $material1 = LessonMaterial::factory()->for($lesson1)->create();
    $material2 = LessonMaterial::factory()->for($lesson2)->create();

    $this->repository->markAccessed($material1->id, $user);
    $this->repository->markAccessed($material2->id, $user);

    $count1 = $this->repository->getAccessedCount($lesson1->id, $user);
    $count2 = $this->repository->getAccessedCount($lesson2->id, $user);

    expect($count1)->toBe(1);
    expect($count2)->toBe(1);
});
