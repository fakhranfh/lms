<?php

namespace Database\Seeders;

use App\Models\PricingTier;
use Illuminate\Database\Seeder;

class PricingTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PricingTier::query()->delete();

        $basicTier = PricingTier::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Free tier for getting started',
            'price' => 0,
            'currency' => 'IDR',
            'billing_period' => 'forever',
            'is_active' => true,
        ]);

        $basicTier->limits()->createMany([
            ['limit_key' => 'student_capacity_per_course', 'limit_value' => 500],
            ['limit_key' => 'video_storage_gb', 'limit_value' => 100],
            ['limit_key' => 'live_session_duration_minutes', 'limit_value' => 0],
        ]);

        $plusTier = PricingTier::create([
            'name' => 'Plus',
            'slug' => 'plus',
            'description' => 'Enhanced learning tools for growing schools',
            'price' => 299000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);

        $plusTier->features()->createMany([
            ['feature_key' => 'analytics', 'is_enabled' => true],
            ['feature_key' => 'live_session', 'is_enabled' => true],
        ]);

        $plusTier->limits()->createMany([
            ['limit_key' => 'student_capacity_per_course', 'limit_value' => 1000],
            ['limit_key' => 'video_storage_gb', 'limit_value' => 500],
            ['limit_key' => 'live_session_duration_minutes', 'limit_value' => 120],
        ]);

        $proTier = PricingTier::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Professional features for scaling institutions',
            'price' => 799000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);

        $proTier->features()->createMany([
            ['feature_key' => 'analytics', 'is_enabled' => true],
            ['feature_key' => 'live_session', 'is_enabled' => true],
            ['feature_key' => 'live_session_recording', 'is_enabled' => true],
            ['feature_key' => 'api_access', 'is_enabled' => true],
        ]);

        $proTier->limits()->createMany([
            ['limit_key' => 'student_capacity_per_course', 'limit_value' => 5000],
            ['limit_key' => 'video_storage_gb', 'limit_value' => 2000],
            ['limit_key' => 'live_session_duration_minutes', 'limit_value' => null],
        ]);

        $maxTier = PricingTier::create([
            'name' => 'Max',
            'slug' => 'max',
            'description' => 'Enterprise features with unlimited capabilities',
            'price' => 1999000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);

        $maxTier->features()->createMany([
            ['feature_key' => 'analytics', 'is_enabled' => true],
            ['feature_key' => 'live_session', 'is_enabled' => true],
            ['feature_key' => 'live_session_recording', 'is_enabled' => true],
            ['feature_key' => 'api_access', 'is_enabled' => true],
            ['feature_key' => 'custom_branding', 'is_enabled' => true],
            ['feature_key' => 'sso', 'is_enabled' => true],
            ['feature_key' => 'priority_support', 'is_enabled' => true],
        ]);

        $maxTier->limits()->createMany([
            ['limit_key' => 'student_capacity_per_course', 'limit_value' => null],
            ['limit_key' => 'video_storage_gb', 'limit_value' => null],
            ['limit_key' => 'live_session_duration_minutes', 'limit_value' => null],
        ]);
    }
}
