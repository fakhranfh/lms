<?php

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Livewire\MyTransactions;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\PaymentGatewayType;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use App\Services\TierChangeService;
use Livewire\Livewire;

it('only lists transactions initiated by the current user', function () {
    $user = User::factory()->create(['school_id' => null]);
    $otherUser = User::factory()->create(['school_id' => null]);

    $school = School::factory()->create();

    $mine = PaymentTransaction::factory()->create([
        'school_id' => $school->id,
        'initiated_by' => $user->id,
        'status' => PaymentStatus::Pending,
    ]);

    $notMine = PaymentTransaction::factory()->create([
        'school_id' => $school->id,
        'initiated_by' => $otherUser->id,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee($mine->transaction_id)
        ->assertDontSee($notMine->transaction_id);
});

it('shows a cancel action for pending transactions', function () {
    $user = User::factory()->create(['school_id' => null]);
    $school = School::factory()->create();

    $transaction = PaymentTransaction::factory()->create([
        'school_id' => $school->id,
        'initiated_by' => $user->id,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee("cancelId = '{$transaction->id}'", false);
});

it('does not show a cancel action for completed transactions', function () {
    $user = User::factory()->create(['school_id' => null]);
    $school = School::factory()->create();

    $transaction = PaymentTransaction::factory()->create([
        'school_id' => $school->id,
        'initiated_by' => $user->id,
        'status' => PaymentStatus::Completed,
    ]);

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertDontSee("cancelId = '{$transaction->id}'", false);
});

it('adds transactions to the school admin sidebar', function () {
    $user = User::factory()->create(['school_id' => null]);
    $school = School::factory()->create();
    $school->admins()->attach($user);

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertSee('Transactions');
});

it('lets a user cancel a stuck pending tier change from the transactions list', function () {
    $this->seed('PricingTierSeeder');

    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => null]);
    $school->admins()->attach($user);

    $gatewayType = PaymentGatewayType::where('name', 'midtrans')->first();
    PaymentGatewayModel::factory()->for($gatewayType)->create(['is_enabled' => true]);

    $plusTier = PricingTier::where('slug', 'plus')->first();

    $this->actingAs($user);
    $transaction = app(TierChangeService::class)->initiateTierChange($school, $plusTier, 'midtrans');

    expect($school->schoolTiers()->where('status', SubscriptionStatus::Pending)->exists())->toBeTrue();

    Livewire::actingAs($user)
        ->test(MyTransactions::class)
        ->call('cancelTransaction', $transaction->id)
        ->assertSee('cancelled');

    expect($school->schoolTiers()->where('status', SubscriptionStatus::Pending)->exists())->toBeFalse();

    // Cancelling clears the "already in progress" lock, so a fresh upgrade can start.
    $newTransaction = app(TierChangeService::class)->initiateTierChange($school, $plusTier, 'midtrans');
    expect($newTransaction)->not->toBeNull();
});
