<?php

use App\Services\R2StorageService;

describe('R2StorageService', function () {
    test('configuration is properly set from env', function () {
        $service = app(R2StorageService::class);

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
        $service = app(R2StorageService::class);

        $methods = ['upload', 'delete', 'getSignedUrl', 'checkSchoolQuota', 'enforceQuotaLimit', 'getTotalStorageUsed'];
        foreach ($methods as $method) {
            expect(method_exists($service, $method))->toBeTrue();
        }
    });

    describe('public API', function () {
        test('checkSchoolQuota returns expected structure', function () {
            // Create a mock or skip R2 if not configured
            if (! config('services.r2.access_key_id')) {
                $this->markTestSkipped('R2 credentials not configured');
            }

            $service = app(R2StorageService::class);
            $quota = $service->checkSchoolQuota('test-school');

            expect($quota)->toHaveKeys(['used', 'limit', 'remaining', 'percentage', 'limit_gb'])
                ->and($quota['used'])->toBeInt()
                ->and($quota['remaining'])->toBeInt()
                ->and($quota['percentage'])->toBeFloat()
                ->and($quota['limit_gb'])->toBeInt();
        });
    });

    describe('validateFileContent', function () {
        test('accepts a real MP4 file whose ftyp signature is offset by a box-size prefix', function () {
            $service = app(R2StorageService::class);
            $path = storage_path('dummy-materials/file_example_MP4_480_1_5MG.mp4');

            if (! file_exists($path)) {
                $this->markTestSkipped('Dummy MP4 fixture not present.');
            }

            // Real MP4 files start with a 4-byte box-size field before the
            // "ftyp" signature, so it never sits at byte 0 — this should not throw.
            $service->validateFileContent($path, 'Video');

            expect(true)->toBeTrue();
        });

        test('rejects a file with no matching signature for the given type', function () {
            $service = app(R2StorageService::class);
            $path = tempnam(sys_get_temp_dir(), 'not-a-video');
            file_put_contents($path, 'plain text content, not a real video file');

            try {
                expect(fn () => $service->validateFileContent($path, 'Video'))
                    ->toThrow(InvalidArgumentException::class);
            } finally {
                unlink($path);
            }
        });
    });

    describe('getPublicUrl', function () {
        test('uses the configured custom domain', function () {
            $original = config('services.r2.custom_domain');
            config(['services.r2.custom_domain' => 'https://cdn.example.com']);
            $service = app(R2StorageService::class);

            try {
                expect($service->getPublicUrl('lessons/abc/materials/file.pdf'))
                    ->toBe('https://cdn.example.com/lessons/abc/materials/file.pdf');
            } finally {
                config(['services.r2.custom_domain' => $original]);
                app()->forgetInstance(R2StorageService::class);
            }
        });

        test('falls back to the raw R2 domain when no custom domain is set', function () {
            $original = config('services.r2.custom_domain');
            config(['services.r2.custom_domain' => '']);
            $service = app(R2StorageService::class);

            try {
                expect($service->getPublicUrl('lessons/abc/materials/file.pdf'))
                    ->toContain('r2.cloudflarestorage.com/lessons/abc/materials/file.pdf');
            } finally {
                config(['services.r2.custom_domain' => $original]);
                app()->forgetInstance(R2StorageService::class);
            }
        });
    });
});
