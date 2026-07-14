<?php

use App\Models\PaymentGatewayType;
use App\Models\SchoolPaymentGateway;
use App\Services\CredentialEncryption;
use App\Services\PaymentGateways\XenditGateway;
use Illuminate\Support\Facades\Http;

describe('XenditGateway', function () {
    beforeEach(function () {
        $gatewayType = PaymentGatewayType::firstOrCreate(
            ['name' => 'xendit'],
            ['label' => 'Xendit', 'is_active' => true]
        );
        $this->config = SchoolPaymentGateway::factory()
            ->for($gatewayType)
            ->state(['is_sandbox_mode' => true])
            ->create();

        $this->credentials = [
            'api_key' => 'test_api_key_xyz123',
            'callback_token' => 'test_callback_token_abc456',
        ];

        $this->encryptionService = app(CredentialEncryption::class);
        $this->gateway = new XenditGateway(
            $this->config,
            $this->credentials,
            $this->encryptionService
        );
    });

    test('createInvoice returns success response with transaction data', function () {
        Http::fake([
            '*sandbox.xendit.co*' => Http::response([
                'id' => 'inv_xendit_12345',
                'external_id' => 'inv-123',
                'status' => 'PENDING',
                'amount' => 199000,
                'currency' => 'IDR',
                'invoice_url' => 'https://invoice.xendit.co/invoice/...',
                'expiry_date' => '2026-07-21T14:23:45.123Z',
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
        expect($result['transaction_id'])->toBe('inv_xendit_12345');
        expect($result['status'])->toBe('pending');
        expect($result['currency'])->toBe('IDR');
    });

    test('createInvoice handles request exception gracefully', function () {
        Http::fake([
            '*sandbox.xendit.co*' => Http::response([
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

    test('checkTransactionStatus returns invoice details', function () {
        Http::fake([
            '*sandbox.xendit.co*' => Http::response([
                'id' => 'inv_xendit_12345',
                'status' => 'PAID',
                'amount' => 199000,
                'currency' => 'IDR',
                'payment_method' => 'CREDIT_CARD',
            ]),
        ]);

        $result = $this->gateway->checkTransactionStatus('inv_xendit_12345');

        expect($result)->toHaveKeys(['transaction_id', 'status', 'amount', 'currency', 'payment_method']);
        expect($result['success'])->toBeTrue();
        expect($result['status'])->toBe('paid');
        expect($result['amount'])->toBe(199000);
    });

    test('checkTransactionStatus handles request failure', function () {
        Http::fake([
            '*sandbox.xendit.co*' => Http::response(
                ['error_code' => 'INVOICE_NOT_FOUND'],
                404
            ),
        ]);

        $result = $this->gateway->checkTransactionStatus('invalid_inv_id');

        expect($result)->toHaveKey('success');
        expect($result['success'])->toBeFalse();
        expect($result)->toHaveKey('error');
    });

    test('refund processes successfully', function () {
        Http::fake([
            '*sandbox.xendit.co*' => Http::response([
                'id' => 'refund_xendit_12345',
                'status' => 'COMPLETED',
                'amount' => 199000,
            ]),
        ]);

        $result = $this->gateway->refund('inv_xendit_12345', 199000);

        expect($result)->toBeTrue();
    });

    test('refund handles request failure', function () {
        Http::fake([
            '*sandbox.xendit.co*' => Http::response(
                ['error_code' => 'INVOICE_NOT_FOUND'],
                404
            ),
        ]);

        $result = $this->gateway->refund('invalid_inv_id', 199000);

        expect($result)->toBeFalse();
    });

    test('handleWebhook rejects missing id or status', function () {
        $payload = [
            'external_id' => 'inv-123',
            'amount' => 199000,
        ];

        expect($this->gateway->handleWebhook($payload))->toBeFalse();
    });

    test('handleWebhook handles invalid payload gracefully', function () {
        $payload = null;

        expect($this->gateway->handleWebhook((array) $payload))->toBeFalse();
    });

    test('uses correct base URL for sandbox mode', function () {
        Http::fake([
            '*sandbox.xendit.co*' => Http::response(['id' => 'inv_123']),
        ]);

        $this->gateway->createInvoice([
            'order_id' => 'inv-123',
            'amount' => 199000,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sandbox.xendit.co');
        });
    });

    test('uses correct base URL for production mode', function () {
        $gatewayType = PaymentGatewayType::firstOrCreate(
            ['name' => 'xendit'],
            ['label' => 'Xendit', 'is_active' => true]
        );
        $config = SchoolPaymentGateway::factory()
            ->for($gatewayType)
            ->state(['is_sandbox_mode' => false])
            ->create();

        $gateway = new XenditGateway($config, $this->credentials, $this->encryptionService);

        Http::fake([
            '*api.xendit.co*' => Http::response(['id' => 'inv_123']),
        ]);

        $gateway->createInvoice([
            'order_id' => 'inv-123',
            'amount' => 199000,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.xendit.co') &&
                ! str_contains($request->url(), 'sandbox');
        });
    });

});
