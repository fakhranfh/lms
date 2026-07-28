<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Services\PaymentGateways\MidtransGateway;
use App\Services\PaymentGateways\XenditGateway;

// CredentialEncryption no longer used here; PaymentGatewayCredential model
// handles encryption via its 'encrypted' cast.

class PaymentGatewayFactory
{
    public function make(string $gatewayName, PaymentGatewayModel $config): PaymentGateway
    {
        $credentials = $this->loadCredentials($config);

        return match ($gatewayName) {
            'midtrans' => new MidtransGateway($config, $credentials),
            'xendit' => new XenditGateway($config, $credentials),
            default => throw new \InvalidArgumentException("Unknown payment gateway: {$gatewayName}"),
        };
    }

    private function loadCredentials(PaymentGatewayModel $config): array
    {
        $credentials = [];

        foreach ($config->credentials as $credential) {
            $credentials[$credential->credential_key] = $credential->credential_value;
        }

        return $credentials;
    }
}
