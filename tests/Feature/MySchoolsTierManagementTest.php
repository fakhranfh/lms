<?php

use App\Contracts\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Livewire\MySchools;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\PaymentGatewayType;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\User;
use App\Services\PaymentGatewayFactory;
use App\Services\TierChangeService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery\MockInterface;

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
        ->call('changeTier', $otherSchool->id, $plusTier->id, null)
        ->assertForbidden();
});

it('lets a school admin upgrade a school tier from the my schools page', function () {
    Queue::fake();

    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => null]);
    $user->schools()->attach($school);

    $plusTier = PricingTier::where('slug', 'plus')->first();

    $gatewayType = PaymentGatewayType::where('name', 'midtrans')->first();
    PaymentGatewayModel::factory()->for($gatewayType)->create(['is_enabled' => true]);

    $mockGateway = $this->mock(PaymentGateway::class);
    $mockGateway->shouldReceive('createInvoice')->andReturn([
        'transaction_id' => 'txn-my-schools',
        'redirect_url' => 'https://payment.gateway/pay/txn-my-schools',
    ]);

    $this->mock(PaymentGatewayFactory::class, function (MockInterface $mock) use ($mockGateway) {
        $mock->shouldReceive('make')->andReturn($mockGateway);
    });

    Livewire::actingAs($user)
        ->test(MySchools::class)
        ->call('changeTier', $school->id, $plusTier->id, 'midtrans')
        ->assertRedirect('https://payment.gateway/pay/txn-my-schools');

    $pendingTier = $school->schoolTiers()->where('status', SubscriptionStatus::Pending)->first();
    expect($pendingTier)->not->toBeNull();
    expect($pendingTier->tier_id)->toBe($plusTier->id);
});

it('lets a school admin cancel a pending tier change', function () {
    Queue::fake();

    $school = School::factory()->create();
    $user = User::factory()->create(['school_id' => null]);
    $user->schools()->attach($school);

    $plusTier = PricingTier::where('slug', 'plus')->first();

    $gatewayType = PaymentGatewayType::where('name', 'midtrans')->first();
    PaymentGatewayModel::factory()->for($gatewayType)->create(['is_enabled' => true]);

    $mockGateway = $this->mock(PaymentGateway::class);
    $mockGateway->shouldReceive('createInvoice')->andReturn([
        'redirect_url' => 'https://payment.gateway/pay',
    ]);

    $this->mock(PaymentGatewayFactory::class, function (MockInterface $mock) use ($mockGateway) {
        $mock->shouldReceive('make')->andReturn($mockGateway);
    });

    app(TierChangeService::class)->initiateTierChange($school, $plusTier, 'midtrans');

    Livewire::actingAs($user)
        ->test(MySchools::class)
        ->call('cancelTierChange', $school->id)
        ->assertSee('cancelled');

    $pendingTier = $school->schoolTiers()->where('status', SubscriptionStatus::Pending)->first();
    expect($pendingTier)->toBeNull();
});
