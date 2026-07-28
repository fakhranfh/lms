<?php

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayType;
use App\Services\PaymentGateways\XenditGateway;
use Illuminate\Support\Facades\Http;

describe('XenditGateway', function () {
    beforeEach(function () {
        $gatewayType = PaymentGatewayType::firstOrCreate(
            ['name' => 'xendit'],
            ['label' => 'Xendit', 'is_active' => true]
        );
        $this->config = PaymentGateway::factory()
            ->for($gatewayType)
            ->state(['is_sandbox_mode' => true, 'enabled_channels' => ['QRIS']])
            ->create();

        $this->credentials = [
            'api_key' => 'test_api_key_xyz123',
            'callback_token' => 'test_callback_token_abc456',
        ];

        $this->gateway = new XenditGateway(
            $this->config,
            $this->credentials,
        );
    });

    test('createInvoice returns success response with transaction data', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response([
                'payment_request_id' => 'pr-xendit-12345',
                'reference_id' => 'inv-123',
                'status' => 'REQUIRES_ACTION',
                'request_amount' => 199000,
                'currency' => 'IDR',
                'actions' => [
                    ['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '00020101...'],
                ],
            ]),
        ]);

        $result = $this->gateway->createInvoice([
            'order_id' => 'inv-123',
            'amount' => 199000,
            'customer_email' => 'test@example.com',
            'customer_name' => 'Test User',
            'description' => 'Payment for subscription',
        ]);

        expect($result)->toHaveKeys(['success', 'transaction_id', 'order_id', 'status', 'payment_url', 'amount', 'currency']);
        expect($result['success'])->toBeTrue();
        expect($result['transaction_id'])->toBe('pr-xendit-12345');
        expect($result['status'])->toBe('requires_action');
        expect($result['payment_url'])->toBe('00020101...');
        expect($result['currency'])->toBe('IDR');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v3/payment_requests')
            && $request['channel_code'] === 'QRIS'
            && $request->hasHeader('api-version', '2024-11-11'));
    });

    test('createInvoice handles request exception gracefully', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response([
                'error_code' => 'INVALID_REQUEST',
            ], 400),
        ]);

        $result = $this->gateway->createInvoice([
            'order_id' => 'inv-123',
            'amount' => 199000,
            'customer_email' => 'test@example.com',
        ]);

        expect($result)->toHaveKey('success');
        expect($result['success'])->toBeFalse();
        expect($result)->toHaveKey('error');
    });

    test('createInvoice uses the requested channel over the gateway default', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response([
                'payment_request_id' => 'pr-xendit-12345',
                'status' => 'REQUIRES_ACTION',
            ]),
        ]);

        $this->gateway->createInvoice([
            'order_id' => 'inv-123',
            'amount' => 199000,
            'channel' => 'OVO',
        ]);

        Http::assertSent(fn ($request) => $request['channel_code'] === 'OVO'
            && $request['channel_properties']['success_return_url']
            && $request['channel_properties']['account_mobile_number'] === '+628123456789');
    });

    test('checkTransactionStatus returns invoice details', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response([
                'payment_request_id' => 'pr-xendit-12345',
                'status' => 'SUCCEEDED',
                'request_amount' => 199000,
                'currency' => 'IDR',
                'channel_code' => 'QRIS',
            ]),
        ]);

        $result = $this->gateway->checkTransactionStatus('pr-xendit-12345');

        expect($result)->toHaveKeys(['transaction_id', 'status', 'amount', 'currency', 'payment_method']);
        expect($result['success'])->toBeTrue();
        expect($result['status'])->toBe('succeeded');
        expect($result['amount'])->toBe(199000);

        Http::assertSent(fn ($request) => $request->hasHeader('api-version', '2024-11-11'));
    });

    test('checkTransactionStatus handles request failure', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response(
                ['error_code' => 'PAYMENT_REQUEST_NOT_FOUND'],
                404
            ),
        ]);

        $result = $this->gateway->checkTransactionStatus('invalid_pr_id');

        expect($result)->toHaveKey('success');
        expect($result['success'])->toBeFalse();
        expect($result)->toHaveKey('error');
    });

    test('refund processes successfully', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response([
                'id' => 'refund_xendit_12345',
                'status' => 'COMPLETED',
                'amount' => 199000,
            ]),
        ]);

        $result = $this->gateway->refund('pr-xendit-12345', 199000);

        expect($result)->toBeTrue();
    });

    test('refund handles request failure', function () {
        Http::fake([
            '*api.xendit.co*' => Http::response(
                ['error_code' => 'PAYMENT_REQUEST_NOT_FOUND'],
                404
            ),
        ]);

        $result = $this->gateway->refund('invalid_pr_id', 199000);

        expect($result)->toBeFalse();
    });

    test('handleWebhook rejects missing id or status', function () {
        $payload = [
            'reference_id' => 'inv-123',
            'amount' => 199000,
        ];

        expect($this->gateway->handleWebhook($payload))->toBeFalse();
    });

    test('handleWebhook handles invalid payload gracefully', function () {
        $payload = null;

        expect($this->gateway->handleWebhook((array) $payload))->toBeFalse();
    });

    test('uses the same base URL regardless of sandbox mode', function () {
        $gatewayType = PaymentGatewayType::firstOrCreate(
            ['name' => 'xendit'],
            ['label' => 'Xendit', 'is_active' => true]
        );
        $config = PaymentGateway::factory()
            ->for($gatewayType)
            ->state(['is_sandbox_mode' => false, 'enabled_channels' => ['QRIS']])
            ->create();

        $gateway = new XenditGateway($config, $this->credentials);

        Http::fake([
            '*api.xendit.co*' => Http::response(['payment_request_id' => 'pr-123']),
        ]);

        $gateway->createInvoice([
            'order_id' => 'inv-123',
            'amount' => 199000,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.xendit.co');
        });
    });

});
