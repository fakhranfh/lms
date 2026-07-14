<?php

namespace App\Contracts;

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
}
