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

    public function test_admin_can_create_pricing_tier_with_limits(): void
    {
        $adminUser = User::factory()->create(['school_id' => null]);
        $adminUser->assignRole('Admin');
        $adminUser->givePermissionTo('pricing-tiers.create');

        $tier = PricingTier::factory()->create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
        ]);
        $tier->limits()->create(['limit_key' => 'student_capacity_per_course', 'limit_value' => 1000]);

        $this->assertDatabaseHas('pricing_tiers', [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
        ]);

        $this->assertCount(1, $tier->limits);
    }

    public function test_admin_can_delete_unused_pricing_tier(): void
    {
        $adminUser = User::factory()->create(['school_id' => null]);
        $adminUser->assignRole('Admin');
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
