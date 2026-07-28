<?php

namespace App\Contracts;

use App\Enums\PaymentStatus;

interface PaymentGateway
{
    /**
     * Create an invoice for payment processing.
     */
    public function createInvoice(array $data): array;

    /**
     * Handle incoming webhook from payment gateway.
     */
    public function handleWebhook(array $payload): bool;

    /**
     * Check transaction status with payment gateway.
     */
    public function checkTransactionStatus(string $transactionId): array;

    /**
     * Process refund for a transaction.
     */
    public function refund(string $transactionId, float $amount): bool;

    /**
     * Extract the internal transaction id a webhook payload refers to.
     */
    public function extractWebhookTransactionId(array $payload): ?string;

    /**
     * Map this gateway's webhook status vocabulary to our internal PaymentStatus.
     */
    public function extractWebhookStatus(array $payload): PaymentStatus;
}
