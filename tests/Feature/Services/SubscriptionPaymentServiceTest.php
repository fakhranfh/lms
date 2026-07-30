<?php

use App\Contracts\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\PaymentGatewayType;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolTier;
use App\Services\PaymentGatewayFactory;
use App\Services\SubscriptionPaymentService;

it('creates a payment invoice for subscription', function () {
    $school = School::factory()->create();
    $tier = PricingTier::factory()->create(['price' => 199000]);
    $subscription = SchoolTier::factory()->create(['school_id' => $school->id, 'tier_id' => $tier->id]);

    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'test_gateway']);
    $gateway = PaymentGatewayModel::factory()->create(['gateway_type_id' => $gatewayType->id]);

    $mockGateway = Mockery::mock(PaymentGateway::class);
    $mockGateway->shouldReceive('createInvoice')->andReturn(['invoice_id' => 'inv_123']);

    $factory = Mockery::mock(PaymentGatewayFactory::class);
    $factory->shouldReceive('make')->andReturn($mockGateway);

    app()->instance(PaymentGatewayFactory::class, $factory);
    $service = app(SubscriptionPaymentService::class);
    $result = $service->createPaymentInvoice($subscription, $gateway);

    expect($result)->toEqual(['invoice_id' => 'inv_123']);
});

it('handles failed payments by expiring subscription', function () {
    $school = School::factory()->create();
    $tier = PricingTier::factory()->create();
    $subscription = SchoolTier::factory()->create(['school_id' => $school->id, 'tier_id' => $tier->id, 'status' => 'active']);

    $gatewayType = PaymentGatewayType::factory()->create();
    $gateway = PaymentGatewayModel::factory()->create(['gateway_type_id' => $gatewayType->id]);

    $transaction = PaymentTransaction::factory()->create([
        'school_id' => $school->id,
        'subscription_id' => $subscription->id,
        'payment_gateway_id' => $gateway->id,
        'status' => 'failed',
    ]);

    $factory = Mockery::mock(PaymentGatewayFactory::class);
    app()->instance(PaymentGatewayFactory::class, $factory);
    $service = app(SubscriptionPaymentService::class);

    $service->handleFailedPayment($transaction);

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Expired);
});
