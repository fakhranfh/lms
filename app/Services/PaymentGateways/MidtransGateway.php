<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGateway;
use App\Models\SchoolPaymentGateway;
use App\Services\CredentialEncryption;

class MidtransGateway implements PaymentGateway
{
    public function __construct(
        private readonly SchoolPaymentGateway $config,
        private readonly array $credentials,
        private readonly CredentialEncryption $credentialEncryption,
    ) {}

    public function createInvoice(array $data): array
    {
        // TODO: Implement Midtrans invoice creation
        return [];
    }

    public function handleWebhook(array $payload): bool
    {
        // TODO: Implement Midtrans webhook handling
        return true;
    }

    public function checkTransactionStatus(string $transactionId): array
    {
        // TODO: Implement Midtrans transaction status check
        return [];
    }

    public function refund(string $transactionId, float $amount): bool
    {
        // TODO: Implement Midtrans refund
        return true;
    }
}
