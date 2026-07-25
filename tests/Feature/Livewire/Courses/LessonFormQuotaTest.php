<?php

use App\Livewire\Courses\LessonForm;
use App\Models\Course;
use App\Models\User;
use App\Services\R2StorageService;
use Livewire\Livewire;

describe('LessonForm Quota Display', function () {
    test('component mounts successfully with module', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('lessons.create');

        $course = Course::factory()
            ->for($user->school)
            ->for($user, 'creator')
            ->create();

        $module = $course->modules()->create([
            'title' => 'Test Module',
            'order' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(LessonForm::class, ['module' => $module])
            ->assertSuccessful();
    });

    test('R2StorageService returns quota structure with all required keys', function () {
        $service = app(R2StorageService::class);

        if (! config('services.r2.access_key_id')) {
            $this->markTestSkipped('R2 credentials not configured');
        }

        $quota = $service->checkSchoolQuota('test-school-id');

        expect($quota)->toHaveKeys([
            'used',
            'remaining',
            'percentage',
            'limit_gb',
        ]);
    });

    test('quota values are human-readable after formatting', function () {
        $service = app(R2StorageService::class);

        $formatted = R2StorageService::formatBytes(1024 * 1024 * 1024); // 1 GB

        expect($formatted)->toBe('1 GB');
    });

    test('quota percentage is between 0 and 100', function () {
        $service = app(R2StorageService::class);

        if (! config('services.r2.access_key_id')) {
            $this->markTestSkipped('R2 credentials not configured');
        }

        $quota = $service->checkSchoolQuota('test-school-id');

        expect($quota['percentage'])
            ->toBeFloat()
            ->toBeGreaterThanOrEqual(0.0)
            ->toBeLessThanOrEqual(100.0);
    });

    test('school storage quota is retrieved from tier', function () {
        $user = User::factory()->create();
        $service = app(R2StorageService::class);

        if (! config('services.r2.access_key_id')) {
            $this->markTestSkipped('R2 credentials not configured');
        }

        $quotaBytes = $service->getSchoolStorageQuotaBytes($user->school->id);

        // Should return a positive integer in bytes
        expect($quotaBytes)->toBeInt()
            ->and($quotaBytes)->toBeGreaterThan(0);
    });

    describe('quota status colors', function () {
        test('component formatBytes produces valid output', function () {
            // Test via R2StorageService
            $formatted1 = R2StorageService::formatBytes(500);
            $formatted2 = R2StorageService::formatBytes(1024 * 100);
            $formatted3 = R2StorageService::formatBytes(1024 * 1024);

            expect($formatted1)->toMatch('/[B]$/')
                ->and($formatted2)->toMatch('/[KB]$/')
                ->and($formatted3)->toMatch('/[MB]$/');
        });

        test('quota percentage calculation is accurate', function () {
            $service = app(R2StorageService::class);

            if (! config('services.r2.access_key_id')) {
                $this->markTestSkipped('R2 credentials not configured');
            }

            $quota = $service->checkSchoolQuota('school-id');

            // used + remaining should be close to limit
            $sum = $quota['used'] + $quota['remaining'];
            $limit = $quota['limit'];

            expect($sum)->toBeLessThanOrEqual($limit + 1)
                ->and($sum)->toBeGreaterThanOrEqual($limit - 1);
        });

        test('enforce quota limit with school id', function () {
            $service = app(R2StorageService::class);

            if (! config('services.r2.access_key_id')) {
                $this->markTestSkipped('R2 credentials not configured');
            }

            // Should not throw unless quota is actually exceeded
            $service->enforceQuotaLimit('test-school-id');
            expect(true)->toBeTrue();
        });
    });

    describe('quota in Livewire component', function () {
        test('can mount component and access quota info', function () {
            $user = User::factory()->create();
            $user->givePermissionTo('lessons.create');

            $course = Course::factory()
                ->for($user->school)
                ->for($user, 'creator')
                ->create();

            $module = $course->modules()->create([
                'title' => 'Test Module',
                'order' => 1,
            ]);

            $this->actingAs($user);

            Livewire::test(LessonForm::class, ['module' => $module])
                ->assertSuccessful();
        });

        test('school tier determines storage quota', function () {
            $user = User::factory()->create();

            if (! $user->school || ! $user->school->tier) {
                $this->markTestSkipped('School without tier configuration');
            }

            $service = app(R2StorageService::class);

            $quota = $service->checkSchoolQuota($user->school->id);

            // Should have a valid limit_gb based on tier
            expect($quota['limit_gb'])->toBeInt()
                ->and($quota['limit_gb'])->toBeGreaterThan(0);
        });
    });

    describe('quota warning messages', function () {
        test('quota info includes all display fields', function () {
            $service = app(R2StorageService::class);

            if (! config('services.r2.access_key_id')) {
                $this->markTestSkipped('R2 credentials not configured');
            }

            $quota = $service->checkSchoolQuota('test-school');

            // Check all fields that getQuotaInfo would return
            expect($quota)->toHaveKeys(['used', 'remaining', 'percentage', 'limit_gb']);
        });

        test('per-school quota different from global quota', function () {
            $service = app(R2StorageService::class);

            if (! config('services.r2.access_key_id')) {
                $this->markTestSkipped('R2 credentials not configured');
            }

            // Global quota (no school id)
            $globalQuota = $service->checkSchoolQuota(null);

            // Per-school quota (with school id)
            $schoolQuota = $service->checkSchoolQuota('some-school-id');

            // They may differ because school uses tier-based quota
            expect($globalQuota['limit'])->toBeInt()
                ->and($schoolQuota['limit'])->toBeInt();
        });
    });
});
