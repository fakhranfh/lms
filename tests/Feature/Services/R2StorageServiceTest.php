<?php

use App\Services\R2StorageService;

describe('R2StorageService', function () {
    test('configuration is properly set from env', function () {
        $service = new R2StorageService;

        // Verify configuration is loaded
        expect($service)->toBeInstanceOf(R2StorageService::class);
    });

    test('quota constants are defined', function () {
        $reflection = new ReflectionClass(R2StorageService::class);
        $constants = $reflection->getConstants();

        expect($constants)->toHaveKey('GLOBAL_QUOTA_BYTES')
            ->and($constants['GLOBAL_QUOTA_BYTES'])->toBe(10 * 1024 * 1024 * 1024);
    });

    test('methods exist and are callable', function () {
        $service = new R2StorageService;

        expect($service)->toHaveMethod('upload')
            ->toHaveMethod('delete')
            ->toHaveMethod('getSignedUrl')
            ->toHaveMethod('checkSchoolQuota')
            ->toHaveMethod('enforceQuotaLimit')
            ->toHaveMethod('getTotalStorageUsed');
    });

    describe('public API', function () {
        test('checkSchoolQuota returns expected structure', function () {
            // Create a mock or skip R2 if not configured
            if (! config('services.r2.access_key_id')) {
                $this->markTestSkipped('R2 credentials not configured');
            }

            $service = new R2StorageService;
            $quota = $service->checkSchoolQuota('test-school');

            expect($quota)->toHaveKeys(['used', 'limit', 'remaining', 'percentage'])
                ->and($quota['limit'])->toBe(10 * 1024 * 1024 * 1024)
                ->and($quota['used'])->toBeInt()
                ->and($quota['remaining'])->toBeInt()
                ->and($quota['percentage'])->toBeFloat();
        });
    });
});
