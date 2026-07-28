<?php

namespace App\Repositories\PaymentGatewayTestTransaction;

use App\Models\PaymentGatewayTestTransaction;

interface PaymentGatewayTestTransactionRepositoryInterface
{
    /**
     * Find the dummy transaction stored for a gateway.
     */
    public function findForGateway(string $gatewayId): ?PaymentGatewayTestTransaction;

    /**
     * Store the dummy transaction id, status, and gateway response for a
     * gateway, replacing any previous one.
     *
     * @param  array<string, mixed>  $response
     */
    public function storeForGateway(string $gatewayId, string $transactionId, ?string $status = null, array $response = []): PaymentGatewayTestTransaction;

    /**
     * Update the stored status for a gateway's test transaction.
     */
    public function updateStatus(string $gatewayId, string $status): void;

    /**
     * Update the stored status for the test transaction matching a gateway's
     * own transaction id (e.g. when a webhook reports its outcome).
     */
    public function updateStatusByTransactionId(string $transactionId, string $status): void;
}
