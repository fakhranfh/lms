<?php

use App\Enums\RoleName;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayCredential;
use App\Models\PaymentGatewayTestTransaction;
use App\Models\PaymentGatewayType;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->admin = User::factory()->create(['school_id' => null]);
    $this->admin->assignRole(RoleName::Admin);

    $gatewayType = PaymentGatewayType::firstOrCreate(
        ['name' => 'xendit'],
        ['label' => 'Xendit', 'is_active' => true]
    );

    $this->gateway = PaymentGateway::factory()
        ->for($gatewayType)
        ->state(['is_sandbox_mode' => true, 'enabled_channels' => ['QRIS', 'OVO']])
        ->create();

    PaymentGatewayCredential::factory()
        ->for($this->gateway, 'paymentGateway')
        ->create(['credential_key' => 'api_key', 'credential_value' => 'valid-key']);
});

test('selecting a channel redirects to the channel-specific result page', function () {
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-qris-123',
            'status' => 'REQUIRES_ACTION',
            'request_amount' => 1000,
            'currency' => 'IDR',
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr-string-data'],
            ],
        ]),
    ]);

    $response = $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-connection', ['channel' => 'QRIS']);

    $response->assertRedirect('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result');

    $result = $this->actingAs($this->admin)->get($response->headers->get('Location'));
    $result->assertOk();
    $result->assertViewIs('admin.gateways.test-result.qris');
    $result->assertSee('qr-string-data');
    $result->assertSee('<svg', false);
});

test('test result page shows the ewallet redirect layout for ewallet channels', function () {
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-ovo-123',
            'status' => 'REQUIRES_ACTION',
            'request_amount' => 1000,
            'currency' => 'IDR',
            'actions' => [
                ['type' => 'REDIRECT_CUSTOMER', 'descriptor' => 'WEB_URL', 'value' => 'https://ovo.example/pay'],
            ],
        ]),
    ]);

    $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-connection', ['channel' => 'OVO']);

    $result = $this->actingAs($this->admin)->get('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result');

    $result->assertOk();
    $result->assertViewIs('admin.gateways.test-result.ewallet');
    $result->assertSee('https://ovo.example/pay');
});

test('ewallet result page shows a push notification message when Xendit returns no action', function () {
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-ovo-456',
            'status' => 'REQUIRES_ACTION',
            'request_amount' => 1000,
            'currency' => 'IDR',
            'channel_code' => 'OVO',
            'actions' => [],
        ]),
    ]);

    $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-connection', ['channel' => 'OVO']);

    $result = $this->actingAs($this->admin)->get('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result');

    $result->assertOk();
    $result->assertViewIs('admin.gateways.test-result.ewallet');
    $result->assertSee('push notification');
});

test('test result page redirects back when no test transaction exists yet', function () {
    $result = $this->actingAs($this->admin)->get('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result');

    $result->assertRedirect('http://admin.lms.local/gateways');
    $result->assertSessionHas('error');
});

test('failed channel test does not redirect to the result page', function () {
    Http::fake([
        '*api.xendit.co*' => Http::response(['error_code' => 'INVALID_API_KEY'], 401),
    ]);

    $response = $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-connection', ['channel' => 'QRIS']);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(PaymentGatewayTestTransaction::where('payment_gateway_id', $this->gateway->id)->exists())->toBeFalse();
});

test('simulate payment button appears on channel-specific result pages', function () {
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-qris-789',
            'status' => 'REQUIRES_ACTION',
            'request_amount' => 10000,
            'currency' => 'IDR',
            'actions' => [
                ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => 'qr-string-data'],
            ],
        ]),
    ]);

    $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-connection', ['channel' => 'QRIS']);

    $result = $this->actingAs($this->admin)->get('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result');

    $result->assertOk();
    $result->assertSee('Simulate Payment');
});

test('simulate payment button is hidden for ewallet channels since Xendit does not support simulating them', function () {
    Http::fake([
        '*api.xendit.co*' => Http::response([
            'payment_request_id' => 'pr-ovo-789',
            'status' => 'REQUIRES_ACTION',
            'request_amount' => 10000,
            'currency' => 'IDR',
            'actions' => [],
        ]),
    ]);

    $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-connection', ['channel' => 'OVO']);

    $result = $this->actingAs($this->admin)->get('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result');

    $result->assertOk();
    $result->assertDontSee('Simulate Payment');
});

test('simulating a payment for an ewallet channel is rejected with a clear message', function () {
    PaymentGatewayTestTransaction::factory()
        ->for($this->gateway, 'paymentGateway')
        ->create([
            'transaction_id' => 'pr-ovo-simulate',
            'response' => ['amount' => 10000, 'channel' => 'OVO'],
        ]);

    $response = $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result/simulate');

    $response->assertSessionHas('error');
    expect(session('error'))->toContain("isn't available for OVO");
    Http::assertNothingSent();
});

test('simulating a payment calls the Xendit simulate endpoint and redirects back to the result page', function () {
    PaymentGatewayTestTransaction::factory()
        ->for($this->gateway, 'paymentGateway')
        ->create([
            'transaction_id' => 'pr-simulate-me',
            'response' => ['amount' => 10000, 'channel' => 'QRIS'],
        ]);

    Http::fake([
        '*api.xendit.co*/v3/payment_requests/pr-simulate-me/simulate' => Http::response([
            'status' => 'PENDING',
            'message' => 'A simulated payment for the specified payment request id is being processed.',
        ]),
    ]);

    $response = $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result/simulate');

    $response->assertRedirect('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result');
    $response->assertSessionHas('success');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'pr-simulate-me/simulate')
        && $request['amount'] === 10000);
});

test('simulating a payment with no test transaction redirects with an error', function () {
    $response = $this->actingAs($this->admin)
        ->post('http://admin.lms.local/gateways/'.$this->gateway->id.'/test-result/simulate');

    $response->assertSessionHas('error');
});
