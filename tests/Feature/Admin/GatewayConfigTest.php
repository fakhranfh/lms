<?php

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayCredential;
use App\Models\PaymentGatewayType;
use App\Services\PaymentGatewayConfigService;

beforeEach(function () {
    PaymentGatewayType::query()->delete();
});

test('gateway type exists in database', function () {
    $midtrans = PaymentGatewayType::factory()->create(['name' => 'midtrans', 'label' => 'Midtrans']);

    expect($midtrans->id)->toBeGreaterThan(0);
    expect($midtrans->name)->toBe('midtrans');
});

test('admin can create gateway with credentials', function () {
    PaymentGatewayType::factory()->create(['name' => 'midtrans', 'label' => 'Midtrans']);

    $gateway = PaymentGateway::factory()->create();

    expect(PaymentGateway::count())->toBe(1);
    expect($gateway->gateway_type_id)->toBeGreaterThan(0);
});

test('gateway credentials are created with proper factory', function () {
    $gateway = PaymentGateway::factory()->create();

    $credential = PaymentGatewayCredential::factory()
        ->for($gateway, 'paymentGateway')
        ->create([
            'credential_key' => 'server_key',
            'credential_value' => 'test-secret-123',
        ]);

    expect($credential->credential_key)->toBe('server_key');
    expect($credential->credential_value)->toBe('test-secret-123');
    expect($credential->is_sensitive)->toBeTrue();
});

test('multiple credentials can be stored for one gateway', function () {
    $gateway = PaymentGateway::factory()->create();

    $cred1 = PaymentGatewayCredential::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['credential_key' => 'server_key']);

    $cred2 = PaymentGatewayCredential::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['credential_key' => 'client_key']);

    expect(PaymentGatewayCredential::count())->toBe(2);
    expect($gateway->credentials->count())->toBe(2);
});

test('updating a gateway with blank credential fields keeps the existing credentials', function () {
    $gateway = PaymentGateway::factory()->create();

    PaymentGatewayCredential::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['credential_key' => 'api_key', 'credential_value' => 'super-secret-key']);

    $service = app(PaymentGatewayConfigService::class);

    $service->updateGateway($gateway->id, [
        'is_enabled' => true,
        'is_sandbox_mode' => true,
        'enabled_channels' => ['QRIS'],
        'credentials' => ['api_key' => ''],
    ]);

    $credential = PaymentGatewayCredential::where('payment_gateway_id', $gateway->id)
        ->where('credential_key', 'api_key')
        ->first();

    expect($credential)->not->toBeNull();
    expect($credential->credential_value)->toBe('super-secret-key');
});

test('updating a gateway with a new credential value overwrites the old one', function () {
    $gateway = PaymentGateway::factory()->create();

    PaymentGatewayCredential::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['credential_key' => 'api_key', 'credential_value' => 'old-key']);

    $service = app(PaymentGatewayConfigService::class);

    $service->updateGateway($gateway->id, [
        'credentials' => ['api_key' => 'new-key'],
    ]);

    $credential = PaymentGatewayCredential::where('payment_gateway_id', $gateway->id)
        ->where('credential_key', 'api_key')
        ->first();

    expect($credential->credential_value)->toBe('new-key');
    expect(PaymentGatewayCredential::where('payment_gateway_id', $gateway->id)->count())->toBe(1);
});
