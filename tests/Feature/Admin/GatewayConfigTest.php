<?php

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayCredential;
use App\Models\PaymentGatewayType;

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
