<?php

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TierChangeType;
use App\Exceptions\TierChangeInProgressException;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\PaymentGatewayType;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolTier;
use App\Models\TierChange;
use App\Services\PaymentGatewayFactory;
use App\Services\TierChangeService;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

beforeEach(function () {
    $this->seed('PricingTierSeeder');
});

it('initiates an upgrade with a pending tier and payment transaction', function () {
    Queue::fake();

    $school = School::factory()->create();
    $plusTier = PricingTier::where('slug', 'plus')->first();

    $gatewayType = PaymentGatewayType::where('name', 'midtrans')->first();
    PaymentGatewayModel::factory()
        ->for($gatewayType)
        ->create(['is_enabled' => true]);

    $service = app(TierChangeService::class);
    $transaction = $service->initiateTierChange($school, $plusTier, 'midtrans');

    expect($transaction)->toBeInstanceOf(PaymentTransaction::class);
    expect($transaction->status)->toBe(PaymentStatus::Pending);
    expect($transaction->change_type)->toBe(TierChangeType::Upgrade);
    expect($transaction->tier_name)->toBe($plusTier->name);
    // Fresh upgrade off the free tier has no proration to prorate against,
    // so the full tier price must be charged rather than Rp 0.
    expect((float) $transaction->amount)->toEqual((float) $plusTier->price);

    $pendingTier = $school->schoolTiers()->where('status', SubscriptionStatus::Pending)->first();
    expect($pendingTier)->not->toBeNull();
    expect($pendingTier->tier_id)->toBe($plusTier->id);
    expect($transaction->subscription_id)->toBe($pendingTier->id);
});

it('applies immediate downgrade without payment', function () {
    $school = School::factory()->create();
    $plusTier = PricingTier::where('slug', 'plus')->first();
    $school->update(['tier_id' => $plusTier->id]);
    $school->refresh();

    expect((float) $school->tier->price)->toEqual(299000.0);

    $basicTier = PricingTier::where('slug', 'basic')->first();
    expect((float) $basicTier->price)->toEqual(0.0);

    $existingSchoolTierIds = $school->schoolTiers()->pluck('id');

    $service = app(TierChangeService::class);
    $result = $service->initiateTierChange($school, $basicTier);

    expect($result)->toBeNull();

    $school->refresh();
    expect($school->tier_id)->toBe($basicTier->id);

    // Get the tier change from the new school tier (excluding the one seeded by the factory)
    $newSchoolTier = $school->schoolTiers()->whereNotIn('id', $existingSchoolTierIds)->first();
    expect($newSchoolTier)->not->toBeNull();

    $tierChange = TierChange::where('school_tier_id', $newSchoolTier->id)->first();
    expect($tierChange)->not->toBeNull();
    expect($tierChange->change_type)->toEqual(TierChangeType::Downgrade);
});

it('throws exception when tier change is already in progress', function () {
    Queue::fake();

    $school = School::factory()->create();
    $plusTier = PricingTier::where('slug', 'plus')->first();
    $proTier = PricingTier::where('slug', 'pro')->first();

    $gatewayType = PaymentGatewayType::where('name', 'midtrans')->first();
    PaymentGatewayModel::factory()
        ->for($gatewayType)
        ->create(['is_enabled' => true]);

    $mockGateway = $this->mock(PaymentGateway::class);
    $mockGateway->shouldReceive('createInvoice')->andReturn([
        'payment_url' => 'https://payment.gateway/pay',
    ]);

    $this->mock(PaymentGatewayFactory::class, function (MockInterface $mock) use ($mockGateway) {
        $mock->shouldReceive('make')->andReturn($mockGateway);
    });

    $service = app(TierChangeService::class);
    $service->initiateTierChange($school, $plusTier, 'midtrans');

    expect(fn () => $service->initiateTierChange($school, $proTier, 'midtrans'))
        ->toThrow(TierChangeInProgressException::class);
});

it('cancels pending tier change', function () {
    Queue::fake();

    $school = School::factory()->create();
    $plusTier = PricingTier::where('slug', 'plus')->first();

    $gatewayType = PaymentGatewayType::where('name', 'midtrans')->first();
    PaymentGatewayModel::factory()
        ->for($gatewayType)
        ->create(['is_enabled' => true]);

    $mockGateway = $this->mock(PaymentGateway::class);
    $mockGateway->shouldReceive('createInvoice')->andReturn([
        'payment_url' => 'https://payment.gateway/pay',
    ]);

    $this->mock(PaymentGatewayFactory::class, function (MockInterface $mock) use ($mockGateway) {
        $mock->shouldReceive('make')->andReturn($mockGateway);
    });

    $service = app(TierChangeService::class);
    $service->initiateTierChange($school, $plusTier, 'midtrans');

    $pendingBefore = $school->schoolTiers()->where('status', SubscriptionStatus::Pending)->first();
    expect($pendingBefore)->not->toBeNull();

    $result = $service->cancelTierChange($school);
    expect($result)->toBeTrue();

    $pendingAfter = SchoolTier::where('school_id', $school->id)
        ->where('status', SubscriptionStatus::Expired)->first();
    expect($pendingAfter)->not->toBeNull();
    expect($pendingAfter->status->value)->toBe(SubscriptionStatus::Expired->value);
});

it('finalizes tier change on successful payment', function () {
    $school = School::factory()->create();
    $basicTier = $school->tier;
    $plusTier = PricingTier::where('slug', 'plus')->first();

    $schoolTier = SchoolTier::create([
        'school_id' => $school->id,
        'tier_id' => $plusTier->id,
        'status' => SubscriptionStatus::Pending,
        'started_at' => now(),
        'expires_at' => null,
        'auto_renew' => true,
    ]);

    $transaction = PaymentTransaction::create([
        'school_id' => $school->id,
        'subscription_id' => $schoolTier->id,
        'payment_gateway_id' => PaymentGatewayModel::factory()->create()->id,
        'transaction_id' => 'txn-123',
        'amount' => 100000,
        'currency' => 'IDR',
        'status' => PaymentStatus::Completed,
        'from_tier_id' => (int) $basicTier->id,
        'change_type' => TierChangeType::Upgrade,
        'proration_amount' => 50000,
    ]);

    $service = app(TierChangeService::class);
    $service->finalizeTierChange($transaction);

    $school->refresh();
    expect($school->tier_id)->toBe($plusTier->id);

    $schoolTier->refresh();
    expect($schoolTier->status)->toBe(SubscriptionStatus::Active);

    $tierChange = TierChange::where('school_tier_id', $schoolTier->id)->first();
    expect($tierChange)->not->toBeNull();
    expect($tierChange->from_tier_id)->toBe($basicTier->id);
    expect($tierChange->to_tier_id)->toBe($plusTier->id);
    expect((float) $tierChange->proration_amount)->toEqual(50000.0);
});

it('creates tier change record on immediate change', function () {
    // Test downgrade which creates immediate change
    $school = School::factory()->create();
    $basicTier = $school->tier;
    $plusTier = PricingTier::where('slug', 'plus')->first();

    $school->update(['tier_id' => $plusTier->id]);
    $school->refresh();

    $existingSchoolTierIds = $school->schoolTiers()->pluck('id');

    $service = app(TierChangeService::class);
    $result = $service->initiateTierChange($school, $basicTier);
    expect($result)->toBeNull();

    // Get the new tier change (excluding the one seeded by the factory)
    $newSchoolTier = $school->schoolTiers()->whereNotIn('id', $existingSchoolTierIds)->first();
    expect($newSchoolTier)->not->toBeNull();

    $tierChange = TierChange::where('school_tier_id', $newSchoolTier->id)->first();
    expect($tierChange)->not->toBeNull();
    expect($tierChange->to_tier_id)->toBe($basicTier->id);
    expect($tierChange->change_type)->toEqual(TierChangeType::Downgrade);
});
