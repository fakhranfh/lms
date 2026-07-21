<?php

use App\Enums\TierFeature;
use App\Enums\TierLimit;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use App\Services\FeatureGateService;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed('PricingTierSeeder');
});

describe('FeatureGateService::can()', function () {

    it('returns true when feature is enabled on school tier', function () {
        $featureGate = app(FeatureGateService::class);
        $plusTier = PricingTier::where('slug', 'plus')->first();
        $school = School::factory()->withTier($plusTier)->create();

        $result = $featureGate->can($school, TierFeature::Analytics);

        expect($result)->toBeTrue();
    });

    it('returns true for any school and feature', function () {
        $featureGate = app(FeatureGateService::class);
        $school = School::factory()->create(); // Free tier

        $result = $featureGate->can($school, TierFeature::Analytics);

        expect($result)->toBeTrue();
    });

    it('checks feature access for users via their school', function () {
        $featureGate = app(FeatureGateService::class);
        $plusTier = PricingTier::where('slug', 'plus')->first();
        $school = School::factory()->withTier($plusTier)->create();
        $user = User::factory()->for($school)->create();

        $result = $featureGate->can($user, TierFeature::Analytics);

        expect($result)->toBeTrue();
    });

    it('returns false when user has no school', function () {
        $featureGate = app(FeatureGateService::class);
        $userWithoutSchool = User::factory()->create(['school_id' => null]);

        $result = $featureGate->can($userWithoutSchool, TierFeature::Analytics);

        expect($result)->toBeFalse();
    });

    it('handles different tier feature combinations', function () {
        $featureGate = app(FeatureGateService::class);
        $freeTier = PricingTier::where('slug', 'free')->first();
        $school = School::factory()->withTier($freeTier)->create();

        $liveSessionFeatureEnabled = $featureGate->can($school, TierFeature::LiveSession);

        expect($liveSessionFeatureEnabled)->toBeTrue();
    });
});

describe('FeatureGateService::limit()', function () {
    it('returns limit value when set', function () {
        $featureGate = app(FeatureGateService::class);
        $school = School::factory()->create(); // Basic tier

        $limit = $featureGate->limit($school, TierLimit::MaterialStorageGb);

        expect($limit)->toBe(1); // Basic tier material storage limit
    });

    it('returns null when limit is unlimited', function () {
        $featureGate = app(FeatureGateService::class);
        $maxTier = PricingTier::where('slug', 'max')->first();
        $school = School::factory()->withTier($maxTier)->create();

        $limit = $featureGate->limit($school, TierLimit::MaterialStorageGb);

        expect($limit)->toBeNull(); // Max tier has unlimited storage
    });

    it('returns null when user has no school', function () {
        $featureGate = app(FeatureGateService::class);
        $userWithoutSchool = User::factory()->create(['school_id' => null]);

        $limit = $featureGate->limit($userWithoutSchool, TierLimit::MaterialStorageGb);

        expect($limit)->toBeNull();
    });

    it('checks limit for users via their school', function () {
        $featureGate = app(FeatureGateService::class);
        $school = School::factory()->create();
        $user = User::factory()->for($school)->create();

        $limit = $featureGate->limit($user, TierLimit::MaterialStorageGb);

        expect($limit)->toBeInt();
    });

    it('returns different limits for different tiers', function () {
        $featureGate = app(FeatureGateService::class);
        $freeTier = PricingTier::where('slug', 'free')->first();
        $plusTier = PricingTier::where('slug', 'plus')->first();

        $freeSchool = School::factory()->withTier($freeTier)->create();
        $freeLimit = $featureGate->limit($freeSchool, TierLimit::MaterialStorageGb);

        $plusSchool = School::factory()->withTier($plusTier)->create();
        $plusLimit = $featureGate->limit($plusSchool, TierLimit::MaterialStorageGb);

        expect($freeLimit)->toBeLessThan($plusLimit);
    });
});

describe('FeatureGateService::requireFeature()', function () {
    it('does not throw when feature is available', function () {
        $featureGate = app(FeatureGateService::class);
        $plusTier = PricingTier::where('slug', 'plus')->first();
        $school = School::factory()->withTier($plusTier)->create();

        $featureGate->requireFeature($school, TierFeature::Analytics);

        expect(true)->toBeTrue();
    });

    it('does not throw exception for any feature', function () {
        $featureGate = app(FeatureGateService::class);
        $freeTier = PricingTier::where('slug', 'free')->first();
        $school = School::factory()->withTier($freeTier)->create();

        $featureGate->requireFeature($school, TierFeature::LiveSession);

        expect(true)->toBeTrue();
    });

    it('works with users', function () {
        $featureGate = app(FeatureGateService::class);
        $freeTier = PricingTier::where('slug', 'free')->first();
        $school = School::factory()->withTier($freeTier)->create();
        $user = User::factory()->for($school)->create();

        $featureGate->requireFeature($user, TierFeature::LiveSession);

        expect(true)->toBeTrue();
    });
});

describe('FeatureGateService::isLimitExceeded()', function () {
    it('returns false when usage is below limit', function () {
        $featureGate = app(FeatureGateService::class);
        $school = School::factory()->create();
        $limit = $featureGate->limit($school, TierLimit::MaterialStorageGb);
        $current = $limit ? $limit - 1 : 0;

        $exceeded = $featureGate->isLimitExceeded(
            $school,
            TierLimit::MaterialStorageGb,
            $current
        );

        expect($exceeded)->toBeFalse();
    });

    it('returns true when usage exceeds limit', function () {
        $featureGate = app(FeatureGateService::class);
        $school = School::factory()->create();
        $limit = $featureGate->limit($school, TierLimit::MaterialStorageGb);

        if ($limit) {
            $exceeded = $featureGate->isLimitExceeded(
                $school,
                TierLimit::MaterialStorageGb,
                $limit + 1
            );

            expect($exceeded)->toBeTrue();
        }
    });

    it('returns false when limit is unlimited', function () {
        $featureGate = app(FeatureGateService::class);
        $maxTier = PricingTier::where('slug', 'max')->first();
        $school = School::factory()->withTier($maxTier)->create();

        $exceeded = $featureGate->isLimitExceeded(
            $school,
            TierLimit::MaterialStorageGb,
            999999
        );

        expect($exceeded)->toBeFalse();
    });

    it('returns false when usage equals limit', function () {
        $featureGate = app(FeatureGateService::class);
        $school = School::factory()->create();
        $limit = $featureGate->limit($school, TierLimit::MaterialStorageGb);

        if ($limit) {
            $exceeded = $featureGate->isLimitExceeded(
                $school,
                TierLimit::MaterialStorageGb,
                $limit
            );

            expect($exceeded)->toBeFalse();
        }
    });
});

describe('Authorization Gates', function () {
    it('creates gate for each tier feature', function () {
        foreach (TierFeature::cases() as $feature) {
            $gateName = "use-{$feature->value}";
            expect(Gate::has($gateName))->toBeTrue();
        }
    });

    it('authorizes feature access via gate', function () {
        $plusTier = PricingTier::where('slug', 'plus')->first();
        $school = School::factory()->withTier($plusTier)->create();
        $user = User::factory()->for($school)->create();

        $authorized = Gate::forUser($user)->allows('use-analytics');

        expect($authorized)->toBeTrue();
    });

    it('denies feature access via gate when not available', function () {
        $freeTier = PricingTier::where('slug', 'free')->first();
        $school = School::factory()->withTier($freeTier)->create();
        $user = User::factory()->for($school)->create();

        $authorized = Gate::forUser($user)->allows('use-live-session');

        expect($authorized)->toBeFalse();
    });
});

describe('School Model Feature Methods', function () {
    it('getCurrentTierLimit gets tier limits', function () {
        $school = School::factory()->create();

        $limit = $school->getCurrentTierLimit('material_storage_gb');

        expect($limit)->toBe(1);
    });
});
