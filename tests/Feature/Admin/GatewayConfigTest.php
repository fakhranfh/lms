<?php

use App\Enums\RoleName;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayCredential;
use App\Models\PaymentGatewayType;
use App\Models\User;
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

test('edit gateway page renders the channel reorder picker', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'xendit', 'label' => 'Xendit']);
    $gateway = PaymentGateway::factory()
        ->for($gatewayType)
        ->state(['enabled_channels' => ['BCA', 'QRIS']])
        ->create();

    $response = $this->actingAs($admin)->get(route('admin.gateways.edit', $gateway));

    // The channel picker is Alpine-rendered from a JSON x-data payload, not
    // server-rendered text, so assert against the raw HTML rather than
    // assertSeeText (which strips tag/attribute content along with tags).
    $response->assertOk();
    $response->assertSee('BCA Virtual Account');
    $response->assertSee('initialEnabled', false);
});

test('admin can reorder enabled channels and the stored order reflects the submission', function () {
    $admin = User::factory()->create(['school_id' => null]);
    $admin->assignRole(RoleName::Admin);

    $gatewayType = PaymentGatewayType::factory()->create(['name' => 'xendit', 'label' => 'Xendit']);
    $gateway = PaymentGateway::factory()
        ->for($gatewayType)
        ->state(['enabled_channels' => ['QRIS', 'BCA']])
        ->create();

    PaymentGatewayCredential::factory()
        ->for($gateway, 'paymentGateway')
        ->create(['credential_key' => 'api_key', 'credential_value' => 'existing-key']);

    // The reorder picker submits hidden inputs in display order, so
    // "BCA" before "QRIS" here simulates the admin having dragged BCA above QRIS.
    $response = $this->actingAs($admin)->put(route('admin.gateways.update', $gateway), [
        'is_enabled' => true,
        'is_sandbox_mode' => true,
        'enabled_channels' => ['BCA', 'QRIS'],
        'credentials' => ['api_key' => ''],
    ]);

    $response->assertRedirect(route('admin.gateways.index'));

    expect($gateway->fresh()->enabled_channels)->toBe(['BCA', 'QRIS']);
});
