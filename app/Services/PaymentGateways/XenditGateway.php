<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGateway;
use App\Models\SchoolPaymentGateway;
use App\Services\CredentialEncryption;

class XenditGateway implements PaymentGateway
{
    public function __construct(
        private readonly SchoolPaymentGateway $config,
        private readonly array $credentials,
        private readonly CredentialEncryption $credentialEncryption,
    ) {}

    public function createInvoice(array $data): array
    {
        // TODO: Implement Xendit invoice creation
        return [];
    }

    public function handleWebhook(array $payload): bool
    {
        // TODO: Implement Xendit webhook handling
        return true;
    }

    public function checkTransactionStatus(string $transactionId): array
    {
        // TODO: Implement Xendit transaction status check
        return [];
    }

    public function refund(string $transactionId, float $amount): bool
    {
        // TODO: Implement Xendit refund
        return true;
    }
}
