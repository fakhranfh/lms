<?php

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayType;
use App\Services\CredentialEncryption;
use App\Services\PaymentGateways\MidtransGateway;
use Illuminate\Support\Facades\Http;

describe('MidtransGateway', function () {
    beforeEach(function () {
        $gatewayType = PaymentGatewayType::firstOrCreate(
            ['name' => 'midtrans'],
            ['label' => 'Midtrans', 'is_active' => true]
        );
        $this->config = PaymentGateway::factory()
            ->for($gatewayType)
            ->state(['is_sandbox_mode' => true])
            ->create();

        $this->credentials = [
            'server_key' => 'test_server_key_123',
            'client_key' => 'test_client_key_456',
        ];

        $this->encryptionService = app(CredentialEncryption::class);
        $this->gateway = new MidtransGateway(
            $this->config,
            $this->credentials,
            $this->encryptionService
        );
    });

    test('createInvoice returns success response with transaction data', function () {
        Http::fake([
            '*sandbox.midtrans.com*' => Http::response([
                'status_code' => '201',
                'transaction_id' => 'midtrans_tx_12345',
                'order_id' => 'ord-123',
                'transaction_status' => 'pending',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v1/...',
                'gross_amount' => 199000,
                'currency' => 'IDR',
            ]),
        ]);

        $result = $this->gateway->createInvoice([
            'order_id' => 'ord-123',
            'amount' => 199000,
            'customer_email' => 'test@example.com',
            'customer_name' => 'Test User',
        ]);

        expect($result)->toHaveKeys(['success', 'transaction_id', 'order_id', 'status', 'payment_url', 'amount', 'currency']);
        expect($result['success'])->toBeTrue();
        expect($result['transaction_id'])->toBe('midtrans_tx_12345');
        expect($result['status'])->toBe('pending');
        expect($result['currency'])->toBe('IDR');
    });

    test('createInvoice handles request exception gracefully', function () {
        Http::fake([
            '*sandbox.midtrans.com*' => Http::response([
                'error_messages' => ['Invalid API key'],
            ], 401),
        ]);

        $result = $this->gateway->createInvoice([
            'order_id' => 'ord-123',
            'amount' => 199000,
            'customer_email' => 'test@example.com',
        ]);

        expect($result)->toHaveKey('success');
        expect($result['success'])->toBeFalse();
        expect($result)->toHaveKey('error');
    });

    test('checkTransactionStatus returns transaction details', function () {
        Http::fake([
            '*sandbox.midtrans.com*' => Http::response([
                'transaction_id' => 'midtrans_tx_12345',
                'transaction_status' => 'settlement',
                'gross_amount' => 199000,
                'payment_type' => 'credit_card',
                'settlement_status' => 'settled',
                'currency' => 'IDR',
            ]),
        ]);

        $result = $this->gateway->checkTransactionStatus('midtrans_tx_12345');

        expect($result)->toHaveKeys(['transaction_id', 'status', 'amount', 'currency', 'payment_method']);
        expect($result['success'])->toBeTrue();
        expect($result['status'])->toBe('settlement');
        expect($result['amount'])->toBe(199000);
        expect($result['currency'])->toBe('IDR');
    });

    test('checkTransactionStatus handles request failure', function () {
        Http::fake([
            '*sandbox.midtrans.com*' => Http::response(
                ['error_messages' => ['Transaction not found']],
                404
            ),
        ]);

        $result = $this->gateway->checkTransactionStatus('invalid_tx_id');

        expect($result)->toHaveKey('success');
        expect($result['success'])->toBeFalse();
        expect($result)->toHaveKey('error');
    });

    test('refund processes successfully with full refund', function () {
        Http::fake([
            '*sandbox.midtrans.com*' => Http::response([
                'status_code' => '200',
                'refund_key' => 'refund-midtrans_tx_12345-1234567890',
                'refund_status' => 'success',
            ]),
        ]);

        $result = $this->gateway->refund('midtrans_tx_12345', 199000);

        expect($result)->toBeTrue();
    });

    test('refund handles request failure', function () {
        Http::fake([
            '*sandbox.midtrans.com*' => Http::response(
                ['error_messages' => ['Transaction not found']],
                404
            ),
        ]);

        $result = $this->gateway->refund('invalid_tx_id', 199000);

        expect($result)->toBeFalse();
    });

    test('handleWebhook validates signature correctly', function () {
        $serverKey = $this->credentials['server_key'];
        $orderId = 'ord-123';
        $statusCode = '200';
        $grossAmount = '199000';
        $validSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature' => $validSignature,
            'transaction_id' => 'midtrans_tx_12345',
            'transaction_status' => 'settlement',
        ];

        $result = $this->gateway->handleWebhook($payload);

        expect($result)->toBeTrue();
    });

    test('handleWebhook rejects invalid signature', function () {
        $payload = [
            'order_id' => 'ord-123',
            'status_code' => '200',
            'gross_amount' => '199000',
            'signature' => 'invalid_signature',
            'transaction_id' => 'midtrans_tx_12345',
            'transaction_status' => 'settlement',
        ];

        $result = $this->gateway->handleWebhook($payload);

        expect($result)->toBeFalse();
    });

    test('uses correct base URL for sandbox mode', function () {
        Http::fake([
            '*sandbox.midtrans.com*' => Http::response(['status_code' => '201']),
        ]);

        $this->gateway->createInvoice([
            'order_id' => 'ord-123',
            'amount' => 199000,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sandbox.midtrans.com');
        });
    });

    test('uses correct base URL for production mode', function () {
        $gatewayType = PaymentGatewayType::firstOrCreate(
            ['name' => 'midtrans'],
            ['label' => 'Midtrans', 'is_active' => true]
        );
        $config = PaymentGateway::factory()
            ->for($gatewayType)
            ->state(['is_sandbox_mode' => false])
            ->create();

        $gateway = new MidtransGateway($config, $this->credentials, $this->encryptionService);

        Http::fake([
            '*app.midtrans.com*' => Http::response(['status_code' => '201']),
        ]);

        $gateway->createInvoice([
            'order_id' => 'ord-123',
            'amount' => 199000,
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'app.midtrans.com') &&
                ! str_contains($request->url(), 'sandbox');
        });
    });
});
