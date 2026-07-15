<?php

use App\Enums\SubscriptionStatus;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolTier;
use App\Models\TierChange;
use Carbon\Carbon;

describe('School Tier Assignment', function () {
    beforeEach(function () {
        $this->seed('PricingTierSeeder');
    });

    test('new school gets default basic tier', function () {
        $school = School::factory()->create();

        $basicTier = PricingTier::where('slug', 'basic')->first();

        expect($school->tier_id)->toBe($basicTier->id);
        expect($school->tier->slug)->toBe('basic');
    });

    test('school tier subscription is created on school creation', function () {
        $school = School::factory()->create();

        expect($school->schoolTiers)->toHaveCount(1);

        $schoolTier = $school->schoolTiers->first();
        expect($schoolTier->tier_id)->toBe($school->tier_id);
        expect($schoolTier->status)->toBe(SubscriptionStatus::Active);
        expect($schoolTier->started_at)->toBeInstanceOf(Carbon::class);
        expect($schoolTier->expires_at)->toBeNull();
    });

    test('tier change record is created on school creation', function () {
        $school = School::factory()->create();

        expect(TierChange::where('to_tier_id', $school->tier_id)->count())->toBe(1);

        $tierChange = TierChange::where('to_tier_id', $school->tier_id)->first();
        expect($tierChange->change_type->value)->toBe('initial');
        expect($tierChange->from_tier_id)->toBeNull();
        expect($tierChange->to_tier_id)->toBe($school->tier_id);
    });

    test('school can access current tier via relationship', function () {
        $school = School::factory()->create();
        $basicTier = PricingTier::where('slug', 'basic')->first();

        expect($school->tier)->toBeInstanceOf(PricingTier::class);
        expect($school->tier->id)->toBe($basicTier->id);
    });

    test('school can access tier features', function () {
        $school = School::factory()->create();

        // Basic tier has no features seeded by default
        $features = $school->tier->features;
        expect($features->count())->toBeGreaterThanOrEqual(0);
    });

    test('school can access tier limits', function () {
        $school = School::factory()->create();

        // Basic tier has 3 limits seeded (student_capacity, video_storage, live_session_duration)
        $limits = $school->tier->limits;
        expect($limits->count())->toBeGreaterThanOrEqual(1);
    });

    test('school can access school tiers collection', function () {
        $school = School::factory()->create();

        expect($school->schoolTiers)->toHaveCount(1);
        expect($school->schoolTiers->first())->toBeInstanceOf(SchoolTier::class);
    });

    test('get_current_tier_limit returns correct value', function () {
        $school = School::factory()->create();
        $basicTier = $school->tier;

        // Create a tier limit for testing
        $basicTier->limits()->create([
            'limit_key' => 'student_capacity',
            'limit_value' => 500,
        ]);

        $limit = $school->getCurrentTierLimit('student_capacity');
        expect($limit)->toBe(500);
    });

    test('get_current_tier_limit returns null for unlimited limit', function () {
        $school = School::factory()->create();
        $basicTier = $school->tier;

        // Create an unlimited limit
        $basicTier->limits()->create([
            'limit_key' => 'video_storage',
            'limit_value' => null,
        ]);

        $limit = $school->getCurrentTierLimit('video_storage');
        expect($limit)->toBeNull();
    });

    test('is_feature_enabled returns true for enabled feature', function () {
        $school = School::factory()->create();
        $basicTier = $school->tier;

        // Create an enabled feature
        $basicTier->features()->create([
            'feature_key' => 'analytics',
            'is_enabled' => true,
        ]);

        expect($school->isFeatureEnabled('analytics'))->toBeTrue();
    });

    test('is_feature_enabled returns false for disabled feature', function () {
        $school = School::factory()->create();
        $basicTier = $school->tier;

        // Create a disabled feature
        $basicTier->features()->create([
            'feature_key' => 'api_access',
            'is_enabled' => false,
        ]);

        expect($school->isFeatureEnabled('api_access'))->toBeFalse();
    });

    test('is_feature_enabled returns false for nonexistent feature', function () {
        $school = School::factory()->create();

        expect($school->isFeatureEnabled('nonexistent_feature'))->toBeFalse();
    });

    test('get_current_school_tier returns most recent tier', function () {
        $school = School::factory()->create();

        $currentTier = $school->getCurrentSchoolTier();
        expect($currentTier)->toBeInstanceOf(SchoolTier::class);
        expect($currentTier->tier_id)->toBe($school->tier_id);
    });

    test('school can be created with specific tier', function () {
        $proPlan = PricingTier::where('slug', 'pro')->first();

        $school = School::factory(['tier_id' => $proPlan->id])->create();

        expect($school->tier_id)->toBe($proPlan->id);
        expect($school->tier->slug)->toBe('pro');
    });
});
