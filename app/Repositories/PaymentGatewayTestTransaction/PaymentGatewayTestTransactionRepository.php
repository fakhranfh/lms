<?php

namespace App\Repositories\PaymentGatewayTestTransaction;

use App\Models\PaymentGatewayTestTransaction;

class PaymentGatewayTestTransactionRepository implements PaymentGatewayTestTransactionRepositoryInterface
{
    public function findForGateway(string $gatewayId): ?PaymentGatewayTestTransaction
    {
        return PaymentGatewayTestTransaction::where('payment_gateway_id', $gatewayId)->first();
    }

    public function storeForGateway(string $gatewayId, string $transactionId, array $response = []): PaymentGatewayTestTransaction
    {
        return PaymentGatewayTestTransaction::updateOrCreate(
            ['payment_gateway_id' => $gatewayId],
            ['transaction_id' => $transactionId, 'response' => $response]
        );
    }
}
