<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Livewire\Admin\TransactionTable;
use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionTableTest extends TestCase
{
    public function test_super_admin_can_view_transaction_table(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        PaymentTransaction::factory()->create(['transaction_id' => 'trx-visible-123']);

        $response = $this->actingAs($admin)->get('http://admin.lms.local/transactions');

        $response->assertStatus(200)->assertSee('trx-visible-123');
    }

    public function test_non_admin_gets_403(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->forSchool($school)->create();

        $response = $this->actingAs($user)->get('http://admin.lms.local/transactions');

        $response->assertStatus(403);
    }

    public function test_filter_by_status_works(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        PaymentTransaction::factory()->create(['transaction_id' => 'trx-completed', 'status' => PaymentStatus::Completed]);
        PaymentTransaction::factory()->create(['transaction_id' => 'trx-failed', 'status' => PaymentStatus::Failed]);

        Livewire::actingAs($admin)->test(TransactionTable::class)
            ->set('status', PaymentStatus::Completed->value)
            ->assertSee('trx-completed')
            ->assertDontSee('trx-failed');
    }

    public function test_filter_by_date_range_works(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        PaymentTransaction::factory()->create(['transaction_id' => 'trx-old', 'created_at' => now()->subYears(2)]);
        PaymentTransaction::factory()->create(['transaction_id' => 'trx-recent', 'created_at' => now()]);

        Livewire::actingAs($admin)->test(TransactionTable::class)
            ->set('dateFrom', now()->subDay()->toDateString())
            ->assertSee('trx-recent')
            ->assertDontSee('trx-old');
    }

    public function test_search_by_transaction_id_works(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        PaymentTransaction::factory()->create(['transaction_id' => 'trx-findme']);
        PaymentTransaction::factory()->create(['transaction_id' => 'trx-other']);

        Livewire::actingAs($admin)->test(TransactionTable::class)
            ->set('search', 'findme')
            ->assertSee('trx-findme')
            ->assertDontSee('trx-other');
    }
}
