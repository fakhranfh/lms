<?php

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentWebhook;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Models\PaymentGatewayTestTransaction;
use App\Models\PaymentGatewayType;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolTier;
use App\Repositories\PaymentGatewayTestTransaction\PaymentGatewayTestTransactionRepositoryInterface;
use App\Services\PaymentGatewayFactory;
use App\Services\SchoolService;
use App\Services\SubscriptionPaymentService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    PaymentGatewayType::query()->delete();
});

test('webhook controller stores midtrans webhook', function () {
    Queue::fake();
    Bus::fake();

    $school = School::factory()->create();
    $tier = PricingTier::factory()->create();
    $subscription = SchoolTier::factory()
        ->for($school, 'school')
        ->create(['tier_id' => $tier->id]);

    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'midtrans']);
    $gateway = PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $gatewayType->id]);

    $transaction = PaymentTransaction::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['school_id' => $school->id, 'subscription_id' => $subscription->id]);

    $payload = [
        'transaction_id' => $transaction->transaction_id,
        'order_id' => 'ord-123',
        'transaction_status' => 'capture',
        'gross_amount' => 100000,
        'currency' => 'IDR',
        'custom_field1' => json_encode(['school_id' => $school->id]),
        'signature_key' => 'dummy-signature',
    ];

    $response = $this->postJson('/webhooks/midtrans', $payload);

    $response->assertStatus(200);
    expect(PaymentWebhook::count())->toBe(1);
    expect(PaymentWebhook::first()->event_type)->toBe('transaction.capture');
});

test('webhook controller stores xendit webhook', function () {
    Queue::fake();
    Bus::fake();

    $school = School::factory()->create();
    $xenditType = PaymentGatewayType::factory()->create(['name' => 'xendit']);
    $xenditGateway = PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $xenditType->id]);

    $payload = [
        'event' => 'payment.succeeded',
        'api_version' => 'v3',
        'data' => [
            'payment_request_id' => 'pr-123',
            'status' => 'SUCCEEDED',
            'request_amount' => 100000,
            'metadata' => ['school_id' => $school->id],
        ],
    ];

    $response = $this->postJson('/webhooks/xendit', $payload);

    $response->assertStatus(200);
    expect(PaymentWebhook::count())->toBe(1);
    expect(PaymentWebhook::first()->event_type)->toBe('payment.succeeded');
});

test('webhook controller returns 404 for unknown gateway', function () {
    $payload = ['transaction_id' => 'txn-123', 'transaction_status' => 'capture'];

    $response = $this->postJson('/webhooks/unknown-gateway', $payload);

    $response->assertStatus(404);
});

test('webhook controller returns 404 when no gateway is configured', function () {
    PaymentGatewayType::factory()->create(['name' => 'midtrans']);

    $payload = [
        'transaction_id' => 'txn-123',
        'transaction_status' => 'capture',
    ];

    $response = $this->postJson('/webhooks/midtrans', $payload);

    $response->assertStatus(404);
});

test('webhook controller rejects invalid payload', function () {
    $payload = [
        'invalid_field' => 'value',
    ];

    $response = $this->postJson('/webhooks/midtrans', $payload);

    $response->assertStatus(400);
});

test('webhook stores encrypted payload', function () {
    Queue::fake();
    Bus::fake();

    $school = School::factory()->create();
    $tier = PricingTier::factory()->create();
    $subscription = SchoolTier::factory()
        ->for($school, 'school')
        ->create(['tier_id' => $tier->id]);

    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'midtrans']);
    $gateway = PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $gatewayType->id]);

    $transaction = PaymentTransaction::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['school_id' => $school->id, 'subscription_id' => $subscription->id]);

    $payload = [
        'transaction_id' => $transaction->transaction_id,
        'transaction_status' => 'capture',
        'custom_field1' => json_encode(['school_id' => $school->id]),
        'sensitive_data' => 'secret-value',
    ];

    $this->postJson('/webhooks/midtrans', $payload);

    $webhook = PaymentWebhook::first();
    expect($webhook->payload)->toBe(json_encode($payload));
    expect($webhook->payload)->toContain($transaction->transaction_id);
});

test('webhook controller dispatches processing job', function () {
    Queue::fake();
    Bus::fake();

    $school = School::factory()->create();
    $tier = PricingTier::factory()->create();
    $subscription = SchoolTier::factory()
        ->for($school, 'school')
        ->create(['tier_id' => $tier->id]);

    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'midtrans']);
    $gateway = PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $gatewayType->id]);

    $transaction = PaymentTransaction::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['school_id' => $school->id, 'subscription_id' => $subscription->id]);

    $payload = [
        'transaction_id' => $transaction->transaction_id,
        'transaction_status' => 'capture',
        'custom_field1' => json_encode(['school_id' => $school->id]),
    ];

    $this->postJson('/webhooks/midtrans', $payload);

    Bus::assertDispatched(ProcessPaymentWebhook::class);
});

test('webhook processing job marks webhook as processed', function () {
    $school = School::factory()->create();
    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'midtrans']);
    $gateway = PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $gatewayType->id]);

    $transaction = PaymentTransaction::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['school_id' => $school->id, 'status' => 'pending']);

    $webhook = PaymentWebhook::factory()
        ->for($gateway, 'paymentGateway')
        ->create([
            'event_type' => 'transaction.capture',
            'payload' => json_encode([
                'transaction_id' => $transaction->transaction_id,
                'transaction_status' => 'capture',
            ]),
            'processed' => false,
        ]);

    $mockGateway = Mockery::mock(PaymentGateway::class);
    $mockGateway->shouldReceive('handleWebhook')->andReturn(true);
    $mockGateway->shouldReceive('extractWebhookTransactionId')->andReturn($transaction->transaction_id);
    $mockGateway->shouldReceive('extractWebhookStatus')->andReturn(PaymentStatus::Completed);

    $mockFactory = Mockery::mock(PaymentGatewayFactory::class);
    $mockFactory->shouldReceive('make')->andReturn($mockGateway);

    $paymentService = new SubscriptionPaymentService($mockFactory);

    $job = new ProcessPaymentWebhook($webhook);
    $job->handle($paymentService, $mockFactory, app(PaymentGatewayTestTransactionRepositoryInterface::class), app(SchoolService::class));

    $webhook->refresh();
    expect($webhook->processed)->toBeTrue();
    expect($webhook->processed_at)->not->toBeNull();
});

test('webhook processing job updates transaction status', function () {
    $school = School::factory()->create();
    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'midtrans']);
    $gateway = PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $gatewayType->id]);

    $transaction = PaymentTransaction::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['school_id' => $school->id]);

    $webhook = PaymentWebhook::factory()
        ->for($gateway, 'paymentGateway')
        ->create([
            'event_type' => 'transaction.capture',
            'payload' => json_encode([
                'transaction_id' => $transaction->transaction_id,
                'transaction_status' => 'capture',
            ]),
            'processed' => false,
        ]);

    $job = new ProcessPaymentWebhook($webhook);
    $job->handle(app(SubscriptionPaymentService::class), app(PaymentGatewayFactory::class), app(PaymentGatewayTestTransactionRepositoryInterface::class), app(SchoolService::class));

    $transaction->refresh();
    expect($transaction->status)->toBe(PaymentStatus::Completed);
});

test('webhook processing job updates the matching test transaction status', function () {
    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'midtrans']);
    $gateway = PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $gatewayType->id]);

    $testTransaction = PaymentGatewayTestTransaction::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['transaction_id' => 'test-tx-123', 'status' => 'pending']);

    $webhook = PaymentWebhook::factory()
        ->for($gateway, 'paymentGateway')
        ->create([
            'event_type' => 'transaction.capture',
            'payload' => json_encode([
                'transaction_id' => 'test-tx-123',
                'transaction_status' => 'capture',
            ]),
            'processed' => false,
        ]);

    $job = new ProcessPaymentWebhook($webhook);
    $job->handle(app(SubscriptionPaymentService::class), app(PaymentGatewayFactory::class), app(PaymentGatewayTestTransactionRepositoryInterface::class), app(SchoolService::class));

    $testTransaction->refresh();
    expect($testTransaction->status)->toBe(PaymentStatus::Completed->value);
});

test('webhook processing job skips if already processed', function () {
    $school = School::factory()->create();
    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'midtrans']);
    $gateway = PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $gatewayType->id]);

    $transaction = PaymentTransaction::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['school_id' => $school->id]);

    $webhook = PaymentWebhook::factory()
        ->for($gateway, 'paymentGateway')
        ->create([
            'event_type' => 'transaction.capture',
            'payload' => json_encode([
                'transaction_id' => $transaction->transaction_id,
                'transaction_status' => 'capture',
            ]),
            'processed' => true,
            'processed_at' => now(),
        ]);

    $originalStatus = $transaction->status;

    $job = new ProcessPaymentWebhook($webhook);
    $job->handle(app(SubscriptionPaymentService::class), app(PaymentGatewayFactory::class), app(PaymentGatewayTestTransactionRepositoryInterface::class), app(SchoolService::class));

    $transaction->refresh();
    expect($transaction->status)->toBe($originalStatus);
});

test('webhook applies rate limiting', function () {
    Queue::fake();
    Bus::fake();

    $school = School::factory()->create();
    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'midtrans']);
    PaymentGatewayModel::factory()
        ->create(['gateway_type_id' => $gatewayType->id]);

    for ($i = 0; $i < 5; $i++) {
        $payload = [
            'transaction_id' => "txn-$i",
            'transaction_status' => 'capture',
            'custom_field1' => json_encode(['school_id' => $school->id]),
        ];
        $this->postJson('/webhooks/midtrans', $payload);
    }

    expect(PaymentWebhook::count())->toBeGreaterThanOrEqual(5);
});
