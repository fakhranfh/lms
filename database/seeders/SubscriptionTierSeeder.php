<?php

namespace Database\Seeders;

use App\Models\SubscriptionTier;
use Illuminate\Database\Seeder;

class SubscriptionTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SubscriptionTier::query()->delete();

        SubscriptionTier::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Perfect for small schools just getting started',
            'price' => 199000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'features' => [
                'class_management',
                'assignment_submission',
                'basic_grading',
                'email_notifications',
                'basic_reporting',
            ],
            'max_users' => 100,
            'storage_gb' => 5,
            'is_active' => true,
        ]);

        SubscriptionTier::create([
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'For growing schools and institutions',
            'price' => 499000,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'features' => [
                'class_management',
                'assignment_submission',
                'basic_grading',
                'email_notifications',
                'basic_reporting',
                'advanced_analytics',
                'custom_branding',
                'api_access',
                'lti_integration',
                'video_hosting_25hrs',
            ],
            'max_users' => 500,
            'storage_gb' => 50,
            'is_active' => true,
        ]);

        SubscriptionTier::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'For large organizations with custom needs',
            'price' => 0,
            'currency' => 'IDR',
            'billing_period' => 'monthly',
            'features' => [
                'class_management',
                'assignment_submission',
                'basic_grading',
                'email_notifications',
                'basic_reporting',
                'advanced_analytics',
                'custom_branding',
                'api_access',
                'lti_integration',
                'video_hosting_25hrs',
                'unlimited_video',
                'custom_integration',
                'sso_saml',
                'dedicated_server',
                'custom_sla',
            ],
            'max_users' => null,
            'storage_gb' => null,
            'is_active' => true,
        ]);
    }
}
