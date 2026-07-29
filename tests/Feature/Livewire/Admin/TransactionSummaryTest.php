<?php

namespace Tests\Feature\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Livewire\Admin\TransactionSummary;
use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionSummaryTest extends TestCase
{
    public function test_super_admin_can_view_transaction_summary(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        PaymentTransaction::factory()->create(['status' => PaymentStatus::Completed, 'amount' => 100000]);

        $response = $this->actingAs($admin)->get('http://admin.lms.local/transactions/summary');

        $response->assertStatus(200)->assertSee('Transaction Summary');
    }

    public function test_non_admin_gets_403(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->forSchool($school)->create();

        $response = $this->actingAs($user)->get('http://admin.lms.local/transactions/summary');

        $response->assertStatus(403);
    }

    public function test_total_revenue_only_counts_completed_transactions(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        PaymentTransaction::factory()->create(['status' => PaymentStatus::Completed, 'amount' => 100000]);
        PaymentTransaction::factory()->create(['status' => PaymentStatus::Completed, 'amount' => 50000]);
        PaymentTransaction::factory()->create(['status' => PaymentStatus::Pending, 'amount' => 999999]);

        Livewire::actingAs($admin)->test(TransactionSummary::class)
            ->assertSet('dateFrom', null)
            ->assertViewHas('totalRevenue', fn ($totalRevenue) => (float) $totalRevenue === 150000.0)
            ->assertViewHas('totalTransactions', 3);
    }

    public function test_filter_by_date_range_affects_totals(): void
    {
        $admin = User::factory()->create(['school_id' => null]);
        $admin->assignRole(RoleName::Admin);

        PaymentTransaction::factory()->create([
            'status' => PaymentStatus::Completed,
            'amount' => 100000,
            'created_at' => now()->subYears(2),
        ]);

        Livewire::actingAs($admin)->test(TransactionSummary::class)
            ->set('dateFrom', now()->subDay()->toDateString())
            ->assertSee('Rp 0');
    }
}
