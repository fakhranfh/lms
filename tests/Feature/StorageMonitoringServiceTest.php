<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Models\School;
use App\Models\StorageUsageLog;
use App\Services\StorageMonitoringService;

function createStorageMonitoringMaterial(School $school, int $fileSize, bool $active = true): LessonMaterial
{
    $course = Course::factory()->for($school)->create();
    $module = Module::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module)->create();

    return LessonMaterial::factory()
        ->withLesson($lesson)
        ->create(['file_size' => $fileSize, 'is_active' => $active]);
}

/**
 * file_size is an unsigned INT column (max ~4.29 GB per row), so simulating a
 * large total usage requires spreading it across several materials.
 */
function createStorageMonitoringMaterials(School $school, int $totalBytes, bool $active = true): void
{
    $chunk = 300 * 1024 * 1024; // 300 MB per row, safely under the column limit

    while ($totalBytes > 0) {
        $size = min($chunk, $totalBytes);
        createStorageMonitoringMaterial($school, $size, $active);
        $totalBytes -= $size;
    }
}

test('global summary sums active material file sizes across all schools', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    createStorageMonitoringMaterial($schoolA, 1000);
    createStorageMonitoringMaterial($schoolB, 2000);
    createStorageMonitoringMaterial($schoolB, 500, active: false);

    $service = app(StorageMonitoringService::class);
    $summary = $service->globalSummary();

    expect($summary['used_bytes'])->toBe(3000)
        ->and($summary)->toHaveKeys(['used_bytes', 'quota_bytes', 'percentage', 'used_formatted', 'quota_formatted']);
});

test('per-school breakdown sums to global total and excludes inactive materials', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    createStorageMonitoringMaterial($schoolA, 1000);
    createStorageMonitoringMaterial($schoolA, 500);
    createStorageMonitoringMaterial($schoolB, 2000);
    createStorageMonitoringMaterial($schoolB, 999, active: false);

    $service = app(StorageMonitoringService::class);
    $breakdown = $service->perSchoolBreakdown();
    $summary = $service->globalSummary();

    expect($breakdown->sum('used_bytes'))->toBe($summary['used_bytes'])
        ->and($breakdown->firstWhere(fn ($row) => $row['school']->id === $schoolA->id)['material_count'])->toBe(2)
        ->and($breakdown->firstWhere(fn ($row) => $row['school']->id === $schoolB->id)['used_bytes'])->toBe(2000);
});

test('per-school breakdown sorts by requested column and direction', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    createStorageMonitoringMaterial($schoolA, 500);
    createStorageMonitoringMaterial($schoolB, 5000);

    $service = app(StorageMonitoringService::class);
    $breakdown = $service->perSchoolBreakdown('used_bytes', 'asc')
        ->whereIn('school.id', [$schoolA->id, $schoolB->id])
        ->values();

    expect($breakdown->first()['school']->id)->toBe($schoolA->id)
        ->and($breakdown->last()['school']->id)->toBe($schoolB->id);
});

test('filtered materials query only returns active materials for that school', function () {
    $school = School::factory()->create();
    $other = School::factory()->create();

    $material = createStorageMonitoringMaterial($school, 1200);
    createStorageMonitoringMaterial($school, 300, active: false);
    createStorageMonitoringMaterial($other, 999);

    $service = app(StorageMonitoringService::class);
    $materials = $service->filteredMaterialsQuery(['school_id' => $school->id])->get();

    expect($materials)->toHaveCount(1)
        ->and($materials->first()->id)->toBe($material->id);
});

test('logging usage returns the newly crossed threshold only once', function () {
    $school = School::factory()->create();

    $service = app(StorageMonitoringService::class);
    $quotaBytes = $service->globalSummary()['quota_bytes'];

    createStorageMonitoringMaterials($school, (int) ($quotaBytes * 0.85));

    $firstCrossing = $service->logGlobalUsageAndGetNewThreshold();
    expect($firstCrossing)->toBe(80);

    $secondCrossing = $service->logGlobalUsageAndGetNewThreshold();
    expect($secondCrossing)->toBeNull();

    expect(StorageUsageLog::whereNull('school_id')->count())->toBe(2);
});

test('logging usage detects crossing a higher threshold after more uploads', function () {
    $school = School::factory()->create();

    $service = app(StorageMonitoringService::class);
    $quotaBytes = $service->globalSummary()['quota_bytes'];

    createStorageMonitoringMaterials($school, (int) ($quotaBytes * 0.85));
    expect($service->logGlobalUsageAndGetNewThreshold())->toBe(80);

    createStorageMonitoringMaterials($school, (int) ($quotaBytes * 0.10));
    expect($service->logGlobalUsageAndGetNewThreshold())->toBe(90);
});
