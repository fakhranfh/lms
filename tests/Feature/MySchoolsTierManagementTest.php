<?php

use App\Enums\SubscriptionStatus;
use App\Livewire\MySchools;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\PaymentGatewayType;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use App\Services\TierChangeService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed('PricingTierSeeder');
});

it('shows the current tier and storage limit for every administered school', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => null]);
    $user->schools()->attach($school);

    Livewire::actingAs($user)
        ->test(MySchools::class)
        ->assertSee($school->tier->name)
        ->assertSee('Storage');
});

it('refuses to change the tier of a school the user does not administer', function () {
    $otherSchool = School::factory()->create();
    $user = User::factory()->create(['school_id' => null]);

    $plusTier = PricingTier::where('slug', 'plus')->first();

    Livewire::actingAs($user)
        ->test(MySchools::class)
        ->call('changeTier', $otherSchool->id, $plusTier->id)
        ->assertForbidden();
});

it('refuses a paid upgrade now that the payment checkout flow is removed', function () {
    Queue::fake();

    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => null]);
    $user->schools()->attach($school);

    $plusTier = PricingTier::where('slug', 'plus')->first();

    $gatewayType = PaymentGatewayType::where('name', 'midtrans')->first();
    PaymentGatewayModel::factory()->for($gatewayType)->create(['is_enabled' => true]);

    Livewire::actingAs($user)
        ->test(MySchools::class)
        ->call('changeTier', $school->id, $plusTier->id)
        ->assertHasErrors(['tier']);
});

it('lets a school admin cancel a pending tier change', function () {
    Queue::fake();

    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => null]);
    $user->schools()->attach($school);

    $plusTier = PricingTier::where('slug', 'plus')->first();

    $gatewayType = PaymentGatewayType::where('name', 'midtrans')->first();
    PaymentGatewayModel::factory()->for($gatewayType)->create(['is_enabled' => true]);

    app(TierChangeService::class)->initiateTierChange($school, $plusTier, 'midtrans');

    Livewire::actingAs($user)
        ->test(MySchools::class)
        ->call('cancelTierChange', $school->id)
        ->assertSee('cancelled');

    $pendingTier = $school->schoolTiers()->where('status', SubscriptionStatus::Pending)->first();
    expect($pendingTier)->toBeNull();
});
