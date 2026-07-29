<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\RoleName;
use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\User;
use Tests\TestCase;

class TransactionShowTest extends TestCase
{
    public function test_super_admin_can_view_transaction_detail(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        $transaction = PaymentTransaction::factory()->create(['transaction_id' => 'trx-detail-123']);

        $response = $this->actingAs($admin)->get("http://admin.lms.local/transactions/{$transaction->id}");

        $response->assertStatus(200)->assertSee('trx-detail-123');
    }

    public function test_tier_purchase_details_are_shown(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        $school = School::factory()->create(['name' => 'Test Academy']);
        $transaction = PaymentTransaction::factory()->create([
            'transaction_id' => 'trx-tier-purchase',
            'school_id' => $school->id,
            'tier_name' => 'Pro',
        ]);

        $response = $this->actingAs($admin)->get("http://admin.lms.local/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertSee('Test Academy')
            ->assertSee('Pro');
    }

    public function test_non_admin_gets_403(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->forSchool($school)->create();

        $transaction = PaymentTransaction::factory()->create();

        $response = $this->actingAs($user)->get("http://admin.lms.local/transactions/{$transaction->id}");

        $response->assertStatus(403);
    }
}
