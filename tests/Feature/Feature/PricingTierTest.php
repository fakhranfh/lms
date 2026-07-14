<?php

use App\Models\PricingTier;

test('pricing tier has features relationship', function () {
    $tier = PricingTier::factory()->create();
    $tier->features()->createMany([
        ['feature_key' => 'analytics', 'is_enabled' => true],
        ['feature_key' => 'live_session', 'is_enabled' => true],
    ]);

    $tier->refresh();
    expect($tier->features)->toHaveCount(2);
    expect($tier->features->first()->feature_key)->toBe('analytics');
});

test('pricing tier has limits relationship', function () {
    $tier = PricingTier::factory()->create();
    $tier->limits()->createMany([
        ['limit_key' => 'student_capacity_per_course', 'limit_value' => 500],
        ['limit_key' => 'video_storage_gb', 'limit_value' => 100],
    ]);

    $tier->refresh();
    expect($tier->limits)->toHaveCount(2);
    expect($tier->limits->first()->limit_key)->toBe('student_capacity_per_course');
});

test('tier features enforces unique constraint on feature_key per tier', function () {
    $tier = PricingTier::factory()->create();
    $tier->features()->create(['feature_key' => 'analytics', 'is_enabled' => true]);

    expect(function () {
        $tier->features()->create(['feature_key' => 'analytics', 'is_enabled' => false]);
    })->toThrow(Exception::class);
});

test('tier limits enforces unique constraint on limit_key per tier', function () {
    $tier = PricingTier::factory()->create();
    $tier->limits()->create(['limit_key' => 'student_capacity_per_course', 'limit_value' => 500]);

    expect(function () {
        $tier->limits()->create(['limit_key' => 'student_capacity_per_course', 'limit_value' => 1000]);
    })->toThrow(Exception::class);
});
