<?php

use App\Models\MediaLibraryItem;
use App\Models\School;
use App\Models\StorageUsageLog;
use App\Services\StorageMonitoringService;

function createStorageMonitoringItem(School $school, int $fileSize): MediaLibraryItem
{
    return MediaLibraryItem::factory()->for($school)->create(['file_size' => $fileSize]);
}

/**
 * file_size is an unsigned INT column (max ~4.29 GB per row), so simulating a
 * large total usage requires spreading it across several items.
 */
function createStorageMonitoringItems(School $school, int $totalBytes): void
{
    $chunk = 300 * 1024 * 1024; // 300 MB per row, safely under the column limit

    while ($totalBytes > 0) {
        $size = min($chunk, $totalBytes);
        createStorageMonitoringItem($school, $size);
        $totalBytes -= $size;
    }
}

test('global summary sums media library item file sizes across all schools', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    createStorageMonitoringItem($schoolA, 1000);
    createStorageMonitoringItem($schoolB, 2000);
    createStorageMonitoringItem($schoolB, 500);

    $service = app(StorageMonitoringService::class);
    $summary = $service->globalSummary();

    expect($summary['used_bytes'])->toBe(3500)
        ->and($summary)->toHaveKeys(['used_bytes', 'quota_bytes', 'percentage', 'used_formatted', 'quota_formatted']);
});

test('per-school breakdown sums to global total', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    createStorageMonitoringItem($schoolA, 1000);
    createStorageMonitoringItem($schoolA, 500);
    createStorageMonitoringItem($schoolB, 2000);

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

    createStorageMonitoringItem($schoolA, 500);
    createStorageMonitoringItem($schoolB, 5000);

    $service = app(StorageMonitoringService::class);
    $breakdown = $service->perSchoolBreakdown('used_bytes', 'asc')
        ->whereIn('school.id', [$schoolA->id, $schoolB->id])
        ->values();

    expect($breakdown->first()['school']->id)->toBe($schoolA->id)
        ->and($breakdown->last()['school']->id)->toBe($schoolB->id);
});

test('logging usage returns the newly crossed threshold only once', function () {
    $school = School::factory()->create();

    $service = app(StorageMonitoringService::class);
    $quotaBytes = $service->globalSummary()['quota_bytes'];

    createStorageMonitoringItems($school, (int) ($quotaBytes * 0.85));

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

    createStorageMonitoringItems($school, (int) ($quotaBytes * 0.85));
    expect($service->logGlobalUsageAndGetNewThreshold())->toBe(80);

    createStorageMonitoringItems($school, (int) ($quotaBytes * 0.10));
    expect($service->logGlobalUsageAndGetNewThreshold())->toBe(90);
});
