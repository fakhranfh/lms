<?php

use App\Models\PricingTier;
use App\Models\School;
use App\Services\TierChangeService;

beforeEach(function () {
    $this->seed('PricingTierSeeder');
});

it('can determine if an upgrade is possible', function () {
    $school = School::factory()->create();
    $basicTier = $school->tier;
    $plusTier = PricingTier::where('slug', 'plus')->first();

    $service = app(TierChangeService::class);

    expect($service->canUpgrade($school, $plusTier))->toBeTrue();
    expect($service->canUpgrade($school, $basicTier))->toBeFalse();
});

it('can determine if a downgrade is possible', function () {
    $school = School::factory()->create();
    $school->update(['tier_id' => PricingTier::where('slug', 'plus')->first()->id]);

    $basicTier = PricingTier::where('slug', 'basic')->first();
    $plusTier = $school->tier;

    $service = app(TierChangeService::class);

    expect($service->canDowngrade($school, $basicTier))->toBeTrue();
    expect($service->canDowngrade($school, $plusTier))->toBeFalse();
});

it('calculates zero proration for free tiers with no active subscription', function () {
    $school = School::factory()->create();
    $newTier = PricingTier::where('slug', 'basic')->first();

    $service = app(TierChangeService::class);

    $proration = $service->calculateProration($school, $newTier);
    expect($proration)->toBe(0.0);
});

it('calculates proration for monthly upgrades', function () {
    $school = School::factory()->create();
    $oldTier = $school->tier;
    $newTier = PricingTier::where('slug', 'plus')->first();

    $schoolTier = $school->schoolTiers()->first();
    $schoolTier->update([
        'expires_at' => now()->addDays(15),
    ]);

    $service = app(TierChangeService::class);

    $proration = $service->calculateProration($school, $newTier);

    $oldDailyRate = $oldTier->price / 30;
    $newDailyRate = $newTier->price / 30;
    $expected = ($newDailyRate - $oldDailyRate) * 15;

    expect(abs($proration - $expected))->toBeLessThan(1);
});

it('calculates negative proration (credit) for downgrades', function () {
    $school = School::factory()->create();
    $school->update(['tier_id' => PricingTier::where('slug', 'plus')->first()->id]);

    $oldTier = $school->tier;
    $newTier = PricingTier::where('slug', 'basic')->first();

    $schoolTier = $school->schoolTiers()->latest('created_at')->first();
    $schoolTier->update([
        'tier_id' => $oldTier->id,
        'expires_at' => now()->addDays(20),
    ]);

    $service = app(TierChangeService::class);

    $proration = $service->calculateProration($school, $newTier);

    expect($proration)->toBeLessThan(0);
});

it('calculates zero proration when current tier has no expiration', function () {
    $school = School::factory()->create();
    $newTier = PricingTier::where('slug', 'plus')->first();

    $schoolTier = $school->schoolTiers()->first();
    $schoolTier->update(['expires_at' => null]);

    $service = app(TierChangeService::class);

    $proration = $service->calculateProration($school, $newTier);
    expect($proration)->toBe(0.0);
});
