<?php

use App\Models\PricingTier;
use App\Models\School;
use App\Services\R2StorageService;

describe('Per-School Quota Management', function () {
    test('basic tier has 1GB material storage quota', function () {
        // Create a fresh tier with limits
        $tier = PricingTier::create([
            'name' => 'TestBasic',
            'slug' => 'test-basic',
            'description' => 'Test basic tier',
            'price' => 0,
            'currency' => 'IDR',
            'billing_period' => 'forever',
            'is_active' => true,
        ]);

        $tier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 1,
        ]);

        $school = School::factory()->create(['tier_id' => $tier->id]);

        $service = new R2StorageService;
        $quotaBytes = $service->getSchoolStorageQuotaBytes($school->id);
        $quotaGb = $quotaBytes / (1024 * 1024 * 1024);

        expect($quotaGb)->toEqual(1.0);
    });

    test('plus tier has 10GB material storage quota', function () {
        $tier = PricingTier::create([
            'name' => 'TestPlus',
            'slug' => 'test-plus',
            'description' => 'Test plus tier',
            'price' => 299000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);

        $tier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 10,
        ]);

        $school = School::factory()->create(['tier_id' => $tier->id]);

        $service = new R2StorageService;
        $quotaBytes = $service->getSchoolStorageQuotaBytes($school->id);
        $quotaGb = $quotaBytes / (1024 * 1024 * 1024);

        expect($quotaGb)->toEqual(10.0);
    });

    test('pro tier has 50GB material storage quota', function () {
        $tier = PricingTier::create([
            'name' => 'TestPro',
            'slug' => 'test-pro',
            'description' => 'Test pro tier',
            'price' => 799000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);

        $tier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 50,
        ]);

        $school = School::factory()->create(['tier_id' => $tier->id]);

        $service = new R2StorageService;
        $quotaBytes = $service->getSchoolStorageQuotaBytes($school->id);
        $quotaGb = $quotaBytes / (1024 * 1024 * 1024);

        expect($quotaGb)->toEqual(50.0);
    });

    test('max tier has 100GB material storage quota', function () {
        $tier = PricingTier::create([
            'name' => 'TestMax',
            'slug' => 'test-max',
            'description' => 'Test max tier',
            'price' => 1999000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);

        $tier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 100,
        ]);

        $school = School::factory()->create(['tier_id' => $tier->id]);

        $service = new R2StorageService;
        $quotaBytes = $service->getSchoolStorageQuotaBytes($school->id);
        $quotaGb = $quotaBytes / (1024 * 1024 * 1024);

        expect($quotaGb)->toEqual(100.0);
    });

    test('checkSchoolQuota returns tier-based limit', function () {
        $tier = PricingTier::create([
            'name' => 'TestTier',
            'slug' => 'test-tier',
            'description' => 'Test tier',
            'price' => 0,
            'currency' => 'IDR',
            'billing_period' => 'forever',
            'is_active' => true,
        ]);

        $tier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 25,
        ]);

        $school = School::factory()->create(['tier_id' => $tier->id]);

        $service = new R2StorageService;

        if (! config('services.r2.access_key_id')) {
            $this->markTestSkipped('R2 credentials not configured');
        }

        $quota = $service->checkSchoolQuota($school->id);

        expect($quota['limit_gb'])->toBe(25)
            ->and($quota['limit'])->toBe(25 * 1024 * 1024 * 1024);
    });

    test('quota enforcement uses school tier limit', function () {
        $tier = PricingTier::create([
            'name' => 'TestEnforce',
            'slug' => 'test-enforce',
            'description' => 'Test enforce',
            'price' => 0,
            'currency' => 'IDR',
            'billing_period' => 'forever',
            'is_active' => true,
        ]);

        $tier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 1,
        ]);

        $school = School::factory()->create(['tier_id' => $tier->id]);

        $service = new R2StorageService;

        if (! config('services.r2.access_key_id')) {
            $this->markTestSkipped('R2 credentials not configured');
        }

        // Should not throw since quota is not exceeded
        $service->enforceQuotaLimit($school->id);
        expect(true)->toBeTrue();
    });
});
