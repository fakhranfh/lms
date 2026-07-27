<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Services\PaymentGateways\MidtransGateway;
use App\Services\PaymentGateways\XenditGateway;

class PaymentGatewayFactory
{
    public function __construct(
        private readonly CredentialEncryption $credentialEncryption
    ) {}

    public function make(string $gatewayName, PaymentGatewayModel $config): PaymentGateway
    {
        $credentials = $this->loadCredentials($config);

        return match ($gatewayName) {
            'midtrans' => new MidtransGateway($config, $credentials, $this->credentialEncryption),
            'xendit' => new XenditGateway($config, $credentials, $this->credentialEncryption),
            default => throw new \InvalidArgumentException("Unknown payment gateway: {$gatewayName}"),
        };
    }

    private function loadCredentials(PaymentGatewayModel $config): array
    {
        $credentials = [];

        foreach ($config->credentials as $credential) {
            $value = $credential->credential_value;
            if ($credential->is_sensitive) {
                $value = $this->credentialEncryption->decrypt($value);
            }
            $credentials[$credential->credential_key] = $value;
        }

        return $credentials;
    }
}
