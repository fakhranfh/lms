<?php

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\User;
use App\Services\LessonMaterialService;

describe('LessonMaterialService', function () {
    describe('material management', function () {
        test('service can be instantiated', function () {
            $service = app(LessonMaterialService::class);
            expect($service)->toBeInstanceOf(LessonMaterialService::class);
        });

        test('materialtype enum has required cases', function () {
            $cases = MaterialType::cases();
            $values = array_map(fn ($c) => $c->value, $cases);

            expect($values)->toContain('Video')
                ->toContain('PDF')
                ->toContain('Document')
                ->toContain('Audio')
                ->toContain('Presentation')
                ->toContain('Image')
                ->toContain('Interactive');
        });
    });

    describe('validation', function () {
        test('material type enum has size limits', function () {
            $maxSizes = [
                MaterialType::Video->maxSize(),
                MaterialType::PDF->maxSize(),
                MaterialType::Document->maxSize(),
                MaterialType::Audio->maxSize(),
                MaterialType::Presentation->maxSize(),
                MaterialType::Image->maxSize(),
                MaterialType::Interactive->maxSize(),
            ];

            // All should be positive numbers
            expect($maxSizes)->each->toBeInt()->each->toBeGreaterThan(0);
        });

        test('material type enum has allowed extensions', function () {
            expect(MaterialType::PDF->allowedExtensions())->toContain('pdf')
                ->and(MaterialType::Video->allowedExtensions())->toContain('mp4')
                ->and(MaterialType::Audio->allowedExtensions())->toContain('mp3');
        });

        test('extensions are lowercase', function () {
            foreach (MaterialType::cases() as $type) {
                $extensions = $type->allowedExtensions();
                expect($extensions)->each->toBe($extensions[0] === strtolower($extensions[0]))
                    ->toBe(true);
            }
        });
    });

    describe('service methods', function () {
        test('service has required methods', function () {
            $service = app(LessonMaterialService::class);

            expect($service)->toHaveMethod('create')
                ->toHaveMethod('update')
                ->toHaveMethod('delete')
                ->toHaveMethod('reorder')
                ->toHaveMethod('getLessonMaterials')
                ->toHaveMethod('getLessonMaterialsByType')
                ->toHaveMethod('markMaterialAsAccessed')
                ->toHaveMethod('isMaterialAccessedBy')
                ->toHaveMethod('getAccessedMaterialCount');
        });
    });

    describe('database integration', function () {
        test('can retrieve lesson materials from database', function () {
            $lesson = Lesson::factory()->create();
            LessonMaterial::factory(3)->create(['lesson_id' => $lesson->id]);

            $service = app(LessonMaterialService::class);
            $materials = $service->getLessonMaterials($lesson->id);

            expect($materials)->toHaveCount(3)
                ->and($materials->first())->toBeInstanceOf(LessonMaterial::class);
        });

        test('materials are ordered by order column', function () {
            $lesson = Lesson::factory()->create();
            $material1 = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'order' => 1]);
            $material2 = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'order' => 2]);
            $material3 = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'order' => 3]);

            $service = app(LessonMaterialService::class);
            $materials = $service->getLessonMaterials($lesson->id);

            expect($materials[0]->order)->toBe(1)
                ->and($materials[1]->order)->toBe(2)
                ->and($materials[2]->order)->toBe(3);
        });

        test('can filter materials by type', function () {
            $lesson = Lesson::factory()->create();
            LessonMaterial::factory(2)->create(['lesson_id' => $lesson->id, 'type' => MaterialType::PDF]);
            LessonMaterial::factory(1)->create(['lesson_id' => $lesson->id, 'type' => MaterialType::Video]);

            $service = app(LessonMaterialService::class);
            $pdfs = $service->getLessonMaterialsByType($lesson->id, MaterialType::PDF);

            expect($pdfs)->toHaveCount(2)
                ->and($pdfs->every(fn ($m) => $m->type === MaterialType::PDF))->toBeTrue();
        });

        test('can track material access', function () {
            $user = User::factory()->create();
            $material = LessonMaterial::factory()->create();

            $service = app(LessonMaterialService::class);
            $service->markMaterialAsAccessed($material->id, $user);

            expect($service->isMaterialAccessedBy($material->id, $user))->toBeTrue();
        });

        test('access count is correct', function () {
            $lesson = Lesson::factory()->create();
            $user = User::factory()->create();
            $material1 = LessonMaterial::factory()->create(['lesson_id' => $lesson->id]);
            $material2 = LessonMaterial::factory()->create(['lesson_id' => $lesson->id]);

            $service = app(LessonMaterialService::class);
            $service->markMaterialAsAccessed($material1->id, $user);

            $count = $service->getAccessedMaterialCount($lesson->id, $user);
            expect($count)->toBe(1);
        });

        test('can delete material from database', function () {
            $material = LessonMaterial::factory()->create();
            $id = $material->id;

            $service = app(LessonMaterialService::class);
            // Skip R2 deletion if not configured
            $deleted = $service->delete($id);

            expect($deleted)->toBe(1);
            expect(LessonMaterial::find($id))->toBeNull();
        });

        test('can reorder materials', function () {
            $lesson = Lesson::factory()->create();
            $m1 = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'order' => 1]);
            $m2 = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'order' => 2]);
            $m3 = LessonMaterial::factory()->create(['lesson_id' => $lesson->id, 'order' => 3]);

            $service = app(LessonMaterialService::class);
            $service->reorder($lesson->id, [$m3->id, $m1->id, $m2->id]);

            expect(LessonMaterial::find($m3->id)->order)->toBe(1)
                ->and(LessonMaterial::find($m1->id)->order)->toBe(2)
                ->and(LessonMaterial::find($m2->id)->order)->toBe(3);
        });
    });
});
