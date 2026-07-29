<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\AdminFeeType;
use App\Enums\RoleName;
use App\Livewire\Admin\BillingSettings;
use App\Models\School;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class BillingSettingsTest extends TestCase
{
    public function test_admin_can_view_billing_settings(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        $response = $this->actingAs($admin)->get('http://admin.lms.local/settings');

        $response->assertStatus(200)->assertSee('Billing Settings');
    }

    public function test_non_admin_gets_403(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->forSchool($school)->create();

        $response = $this->actingAs($user)->get('http://admin.lms.local/settings');

        $response->assertStatus(403);
    }

    public function test_mount_defaults_to_config_values_when_unset(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        Livewire::actingAs($admin)->test(BillingSettings::class)
            ->assertSet('vat_rate', (string) round((float) config('billing.vat_rate') * 100, 2))
            ->assertSet('admin_fee_type', AdminFeeType::Percentage->value)
            ->assertSet('admin_fee_rate', (string) round((float) config('billing.admin_fee_rate') * 100, 2));
    }

    public function test_admin_can_update_percentage_admin_fee(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        Livewire::actingAs($admin)->test(BillingSettings::class)
            ->set('vat_rate', '12')
            ->set('admin_fee_type', AdminFeeType::Percentage->value)
            ->set('admin_fee_rate', '3')
            ->call('update')
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame('0.12', Setting::get('billing.vat_rate'));
        $this->assertSame(AdminFeeType::Percentage->value, Setting::get('billing.admin_fee_type'));
        $this->assertSame('0.03', Setting::get('billing.admin_fee_rate'));
    }

    public function test_admin_can_update_fixed_admin_fee(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        Livewire::actingAs($admin)->test(BillingSettings::class)
            ->set('admin_fee_type', AdminFeeType::Fixed->value)
            ->set('admin_fee_nominal', '5000')
            ->call('update')
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame(AdminFeeType::Fixed->value, Setting::get('billing.admin_fee_type'));
        $this->assertSame('5000', Setting::get('billing.admin_fee_nominal'));
    }

    public function test_validation_rejects_out_of_range_rates(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        Livewire::actingAs($admin)->test(BillingSettings::class)
            ->set('vat_rate', '150')
            ->call('update')
            ->assertHasErrors(['vat_rate']);
    }

    public function test_validation_requires_nominal_when_fixed_type_selected(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        Livewire::actingAs($admin)->test(BillingSettings::class)
            ->set('admin_fee_type', AdminFeeType::Fixed->value)
            ->set('admin_fee_nominal', '')
            ->call('update')
            ->assertHasErrors(['admin_fee_nominal']);
    }
}
