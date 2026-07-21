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

        $freeTier = PricingTier::create([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Free tier for getting started',
            'price' => 0,
            'currency' => 'IDR',
            'billing_period' => 'forever',
            'is_active' => true,
        ]);

        $freeTier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 1,
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

        $plusTier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 10,
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

        $proTier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 50,
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

        $maxTier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => null, // Unlimited
        ]);
    }
}
