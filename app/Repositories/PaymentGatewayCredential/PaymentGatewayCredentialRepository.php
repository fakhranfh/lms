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
        return PaymentGatewayCredential::where('payment_gateway_id', $gatewayId)->delete();
    }

    public function updateOrCreate(string $gatewayId, string $key, string $value): PaymentGatewayCredential
    {
        return PaymentGatewayCredential::updateOrCreate(
            ['payment_gateway_id' => $gatewayId, 'credential_key' => $key],
            ['credential_value' => $value, 'is_sensitive' => true]
        );
    }
}
