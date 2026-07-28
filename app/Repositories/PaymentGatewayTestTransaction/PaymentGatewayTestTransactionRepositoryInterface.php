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
     * Store the dummy transaction id and gateway response for a gateway, replacing any previous one.
     *
     * @param  array<string, mixed>  $response
     */
    public function storeForGateway(string $gatewayId, string $transactionId, array $response = []): PaymentGatewayTestTransaction;
}
