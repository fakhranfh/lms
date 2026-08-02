<?php

use App\Enums\MaterialType;
use App\Models\MediaLibraryItem;
use App\Models\School;
use App\Services\MediaLibraryService;

describe('MediaLibraryService', function () {
    test('service can be instantiated', function () {
        expect(app(MediaLibraryService::class))->toBeInstanceOf(MediaLibraryService::class);
    });

    test('can list media items scoped to a school', function () {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();

        MediaLibraryItem::factory()->withSchool($school)->count(2)->create();
        MediaLibraryItem::factory()->withSchool($otherSchool)->create();

        $service = app(MediaLibraryService::class);
        $items = $service->list($school->id)->get();

        expect($items)->toHaveCount(2)
            ->and($items->every(fn (MediaLibraryItem $item) => $item->school_id === $school->id))->toBeTrue();
    });

    test('can filter media items by type', function () {
        $school = School::factory()->create();

        MediaLibraryItem::factory()->withSchool($school)->withType(MaterialType::PDF)->count(2)->create();
        MediaLibraryItem::factory()->withSchool($school)->withType(MaterialType::Video)->create();

        $service = app(MediaLibraryService::class);
        $pdfs = $service->list($school->id, MaterialType::PDF->value)->get();

        expect($pdfs)->toHaveCount(2)
            ->and($pdfs->every(fn (MediaLibraryItem $item) => $item->type === MaterialType::PDF))->toBeTrue();
    });

    test('can update media metadata', function () {
        $item = MediaLibraryItem::factory()->create(['title' => 'Old Title']);

        $service = app(MediaLibraryService::class);
        $service->updateMetadata($item->id, ['title' => 'New Title']);

        expect(MediaLibraryItem::find($item->id)->title)->toBe('New Title');
    });

    test('can delete a media item from the database', function () {
        $item = MediaLibraryItem::factory()->create();
        $id = $item->id;

        $service = app(MediaLibraryService::class);
        $deleted = $service->delete($id);

        expect($deleted)->toBe(1);
        expect(MediaLibraryItem::find($id))->toBeNull();
    });

    test('finalize upload rejects an invalid material type', function () {
        $service = app(MediaLibraryService::class);

        expect(fn () => $service->finalizeUpload('school-id', null, ['type' => 'NotAType', 'temp_key' => 'temp/media/foo.pdf']))
            ->toThrow(InvalidArgumentException::class);
    });

    test('finalize upload requires a temp key', function () {
        $service = app(MediaLibraryService::class);

        expect(fn () => $service->finalizeUpload('school-id', null, ['type' => 'PDF']))
            ->toThrow(InvalidArgumentException::class, 'temp_key is required');
    });
});
