<?php

namespace App\Repositories\PaymentGatewayCredential;

use App\Models\PaymentGatewayCredential;

class PaymentGatewayCredentialRepository implements PaymentGatewayCredentialRepositoryInterface
{
    public function create(array $data): PaymentGatewayCredential
    {
        return PaymentGatewayCredential::create($data);
    }

    public function deleteForGateway(string $gatewayId): int
    {
        return PaymentGatewayCredential::where('school_payment_gateway_id', $gatewayId)->delete();
    }
}
