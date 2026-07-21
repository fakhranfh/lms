<?php

namespace Database\Seeders;

use App\Models\PricingTier;
use App\Models\School;
use Illuminate\Database\Seeder;

class PricingTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $freeTier = PricingTier::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'description' => 'Free tier for getting started',
                'price' => 0,
                'currency' => 'IDR',
                'billing_period' => 'forever',
                'is_active' => true,
            ]
        );

        $freeTier->limits()->delete();
        $freeTier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 1,
        ]);

        $plusTier = PricingTier::updateOrCreate(
            ['slug' => 'plus'],
            [
                'name' => 'Plus',
                'description' => 'Enhanced learning tools for growing schools',
                'price' => 299000,
                'currency' => 'IDR',
                'billing_period' => 'monthly',
                'is_active' => true,
            ]
        );

        $plusTier->limits()->delete();
        $plusTier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 10,
        ]);

        $proTier = PricingTier::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'Professional features for scaling institutions',
                'price' => 799000,
                'currency' => 'IDR',
                'billing_period' => 'monthly',
                'is_active' => true,
            ]
        );

        $proTier->limits()->delete();
        $proTier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => 50,
        ]);

        $maxTier = PricingTier::updateOrCreate(
            ['slug' => 'max'],
            [
                'name' => 'Max',
                'description' => 'Enterprise features with unlimited capabilities',
                'price' => 1999000,
                'currency' => 'IDR',
                'billing_period' => 'monthly',
                'is_active' => true,
            ]
        );

        $maxTier->limits()->delete();
        $maxTier->limits()->create([
            'limit_key' => 'material_storage_gb',
            'limit_value' => null, // Unlimited
        ]);

        School::query()->update(['tier_id' => $freeTier->id]);
        PricingTier::whereNotIn('id', [$freeTier->id, $plusTier->id, $proTier->id, $maxTier->id])->delete();
    }
}
