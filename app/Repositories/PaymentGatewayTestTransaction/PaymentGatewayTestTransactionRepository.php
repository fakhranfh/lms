<?php

namespace App\Repositories\PaymentGatewayTestTransaction;

use App\Models\PaymentGatewayTestTransaction;

class PaymentGatewayTestTransactionRepository implements PaymentGatewayTestTransactionRepositoryInterface
{
    public function findForGateway(string $gatewayId): ?PaymentGatewayTestTransaction
    {
        return PaymentGatewayTestTransaction::where('payment_gateway_id', $gatewayId)->first();
    }

    public function storeForGateway(string $gatewayId, string $transactionId, ?string $status = null, array $response = []): PaymentGatewayTestTransaction
    {
        return PaymentGatewayTestTransaction::updateOrCreate(
            ['payment_gateway_id' => $gatewayId],
            ['transaction_id' => $transactionId, 'status' => $status, 'response' => $response]
        );
    }

    public function updateStatus(string $gatewayId, string $status): void
    {
        PaymentGatewayTestTransaction::where('payment_gateway_id', $gatewayId)->update(['status' => $status]);
    }

    public function updateStatusByTransactionId(string $transactionId, string $status): void
    {
        PaymentGatewayTestTransaction::where('transaction_id', $transactionId)->update(['status' => $status]);
    }
}
