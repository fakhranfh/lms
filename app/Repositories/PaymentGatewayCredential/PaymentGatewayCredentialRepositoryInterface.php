<?php

namespace App\Repositories\PaymentGatewayCredential;

use App\Models\PaymentGatewayCredential;

interface PaymentGatewayCredentialRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PaymentGatewayCredential;

    /**
     * Delete all credentials belonging to a school payment gateway.
     */
    public function deleteForGateway(string $gatewayId): int;
}
