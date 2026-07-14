<?php

use App\Contracts\PaymentGateway;
use App\Models\PaymentGatewayType;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolPaymentGateway;
use App\Models\SchoolTier;
use App\Services\PaymentGatewayFactory;
use App\Services\SubscriptionPaymentService;

it('creates a payment invoice for subscription', function () {
    $school = School::factory()->create();
    $tier = PricingTier::factory()->create(['price' => 199000]);
    $subscription = SchoolTier::factory()->create(['school_id' => $school->id, 'tier_id' => $tier->id]);

    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'test_gateway']);
    $gateway = SchoolPaymentGateway::factory()->create(['school_id' => $school->id, 'gateway_type_id' => $gatewayType->id]);

    $mockGateway = Mockery::mock(PaymentGateway::class);
    $mockGateway->shouldReceive('createInvoice')->andReturn(['invoice_id' => 'inv_123']);

    $factory = Mockery::mock(PaymentGatewayFactory::class);
    $factory->shouldReceive('make')->andReturn($mockGateway);

    $service = new SubscriptionPaymentService($factory);
    $result = $service->createPaymentInvoice($subscription, $gateway);

    expect($result)->toEqual(['invoice_id' => 'inv_123']);
});

it('handles failed payments by expiring subscription', function () {
    $school = School::factory()->create();
    $tier = PricingTier::factory()->create();
    $subscription = SchoolTier::factory()->create(['school_id' => $school->id, 'tier_id' => $tier->id, 'status' => 'active']);

    $gatewayType = PaymentGatewayType::factory()->create();
    $gateway = SchoolPaymentGateway::factory()->create(['school_id' => $school->id, 'gateway_type_id' => $gatewayType->id]);

    $transaction = PaymentTransaction::factory()->create([
        'school_id' => $school->id,
        'subscription_id' => $subscription->id,
        'school_payment_gateway_id' => $gateway->id,
        'status' => 'failed',
    ]);

    $factory = Mockery::mock(PaymentGatewayFactory::class);
    $service = new SubscriptionPaymentService($factory);

    $service->handleFailedPayment($transaction);

    $subscription->refresh();
    expect($subscription->status)->toBe('expired');
});
