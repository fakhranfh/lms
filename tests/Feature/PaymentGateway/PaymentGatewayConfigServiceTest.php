<?php

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayCredential;
use App\Models\PaymentGatewayTestTransaction;
use App\Models\PaymentGatewayType;
use App\Services\PaymentGatewayConfigService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

describe('PaymentGatewayConfigService::testConnection', function () {
    beforeEach(function () {
        $this->service = app(PaymentGatewayConfigService::class);

        $gatewayType = PaymentGatewayType::firstOrCreate(
            ['name' => 'xendit'],
            ['label' => 'Xendit', 'is_active' => true]
        );

        $this->gateway = PaymentGateway::factory()
            ->for($gatewayType)
            ->state(['is_sandbox_mode' => true])
            ->create();

        PaymentGatewayCredential::factory()
            ->for($this->gateway, 'paymentGateway')
            ->create(['credential_key' => 'api_key', 'credential_value' => 'valid-key']);
    });

    test('creates and stores a dummy transaction on first connection test', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response([
                'payment_request_id' => 'pr-dummy-12345',
                'reference_id' => 'connection-test-abc',
                'status' => 'REQUIRES_ACTION',
            ]),
        ]);

        $result = $this->service->testConnection($this->gateway);

        expect($result['success'])->toBeTrue();

        $testTransaction = PaymentGatewayTestTransaction::where('payment_gateway_id', $this->gateway->id)
            ->where('transaction_id', 'pr-dummy-12345')
            ->first();

        expect($testTransaction)->not->toBeNull();
        expect($testTransaction->status)->toBe('requires_action');
        expect($testTransaction->response)->toMatchArray([
            'success' => true,
            'transaction_id' => 'pr-dummy-12345',
        ]);
    });

    test('reuses the stored dummy transaction instead of creating a new one', function () {
        PaymentGatewayTestTransaction::factory()
            ->for($this->gateway, 'paymentGateway')
            ->create(['transaction_id' => 'pr-existing-999', 'status' => 'requires_action']);

        Http::fake([
            '*api.xendit.co*/v3/payment_requests/pr-existing-999' => Http::response([
                'payment_request_id' => 'pr-existing-999',
                'status' => 'SUCCEEDED',
            ]),
        ]);

        $result = $this->service->testConnection($this->gateway);

        expect($result['success'])->toBeTrue();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'pr-existing-999') && $request->method() === 'GET');
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');

        $testTransaction = PaymentGatewayTestTransaction::where('payment_gateway_id', $this->gateway->id)->first();
        expect($testTransaction->status)->toBe('succeeded');
    });

    test('treats a failed invoice creation as invalid credentials', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response(['error_code' => 'INVALID_API_KEY'], 401),
        ]);

        $result = $this->service->testConnection($this->gateway);

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('check your credentials');
    });

    test('reports connection errors without exposing internal PHP errors', function () {
        Http::fake(function () {
            throw new ConnectionException('Could not resolve host');
        });

        $result = $this->service->testConnection($this->gateway);

        expect($result['success'])->toBeFalse();
        expect($result['message'])->not->toContain('Undefined property');
    });
});
