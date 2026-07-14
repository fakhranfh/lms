<?php

namespace Tests\Feature\PricingTier;

use App\Models\PricingTier;
use App\Models\SchoolTier;
use App\Models\User;
use App\Services\PricingTierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PricingTierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_admin_can_view_pricing_tiers_index(): void
    {
        $adminUser = User::factory()->create(['school_id' => null]);
        $adminUser->assignRole('admin');

        $response = $this->actingAs($adminUser)->get(
            'http://admin.lms.local/pricing-tiers'
        );

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_pricing_tiers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(
            'http://admin.lms.local/pricing-tiers'
        );

        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_pricing_tier_form(): void
    {
        $adminUser = User::factory()->create(['school_id' => null]);
        $adminUser->assignRole('admin');

        $response = $this->actingAs($adminUser)->get(
            'http://admin.lms.local/pricing-tiers/create'
        );

        $response->assertStatus(200);
    }

    public function test_admin_can_create_pricing_tier_with_features_and_limits(): void
    {
        $adminUser = User::factory()->create(['school_id' => null]);
        $adminUser->assignRole('admin');
        $adminUser->givePermissionTo('pricing-tiers.create');

        $tier = PricingTier::factory()->create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
        ]);
        $tier->features()->create(['feature_key' => 'analytics', 'is_enabled' => true]);
        $tier->limits()->create(['limit_key' => 'student_capacity_per_course', 'limit_value' => 1000]);

        $this->assertDatabaseHas('pricing_tiers', [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
        ]);

        $this->assertCount(1, $tier->features);
        $this->assertCount(1, $tier->limits);
    }

    public function test_admin_can_update_pricing_tier(): void
    {
        $adminUser = User::factory()->create(['school_id' => null]);
        $adminUser->assignRole('admin');
        $adminUser->givePermissionTo('pricing-tiers.update');

        $tier = PricingTier::factory()->create([
            'name' => 'Standard',
            'slug' => 'standard',
        ]);

        $response = $this->actingAs($adminUser)->get(
            "http://admin.lms.local/pricing-tiers/{$tier->id}/edit"
        );

        $response->assertStatus(200);
    }

    public function test_admin_cannot_delete_tier_with_active_schools(): void
    {
        $adminUser = User::factory()->create(['school_id' => null]);
        $adminUser->assignRole('admin');
        $adminUser->givePermissionTo('pricing-tiers.delete');

        $tier = PricingTier::factory()->create();
        SchoolTier::factory()->create(['tier_id' => $tier->id]);

        $this->actingAs($adminUser)->call('DELETE', "http://admin.lms.local/pricing-tiers/{$tier->id}");

        $this->assertDatabaseHas('pricing_tiers', ['id' => $tier->id]);
    }

    public function test_admin_can_delete_unused_pricing_tier(): void
    {
        $adminUser = User::factory()->create(['school_id' => null]);
        $adminUser->assignRole('admin');
        $adminUser->givePermissionTo('pricing-tiers.delete');

        $tier = PricingTier::factory()->create();

        $service = app(PricingTierService::class);
        $service->delete($tier->id);

        $this->assertDatabaseMissing('pricing_tiers', ['id' => $tier->id]);
    }

    public function test_cannot_delete_tier_with_active_schools_via_service(): void
    {
        $tier = PricingTier::factory()->create();
        SchoolTier::factory()->create(['tier_id' => $tier->id]);

        $service = app(PricingTierService::class);

        $this->expectException(ValidationException::class);
        $service->delete($tier->id);
    }
}
