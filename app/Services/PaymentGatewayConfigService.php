<?php

namespace App\Services;

use App\Models\PaymentGatewayCredential;
use App\Models\PaymentGatewayType;
use App\Models\SchoolPaymentGateway;
use App\Repositories\SchoolPaymentGateway\SchoolPaymentGatewayRepositoryInterface;

class PaymentGatewayConfigService
{
    public function __construct(
        private readonly SchoolPaymentGatewayRepositoryInterface $repository,
        private readonly PaymentGatewayFactory $gatewayFactory
    ) {}

    public function getAllGateways(array $with = [])
    {
        return $this->repository->get([], $with);
    }

    public function getGateway($id, array $with = ['paymentGatewayType', 'credentials'])
    {
        return $this->repository->find($id);
    }

    public function getAvailableGatewayTypes()
    {
        return PaymentGatewayType::where('is_active', true)->get();
    }

    public function createGateway(array $data): SchoolPaymentGateway
    {
        $gatewayData = [
            'gateway_type_id' => $data['gateway_type_id'],
            'is_enabled' => $data['is_enabled'] ?? false,
            'is_sandbox_mode' => $data['is_sandbox_mode'] ?? true,
            'webhook_secret' => $data['webhook_secret'] ?? null,
        ];

        $gateway = $this->repository->create($gatewayData);

        // Store credentials
        $this->storeCredentials($gateway->id, $data['credentials'] ?? []);

        return $gateway->load(['paymentGatewayType', 'credentials']);
    }

    public function updateGateway($id, array $data): SchoolPaymentGateway
    {
        $gatewayData = [
            'is_enabled' => $data['is_enabled'] ?? false,
            'is_sandbox_mode' => $data['is_sandbox_mode'] ?? true,
            'webhook_secret' => $data['webhook_secret'] ?? null,
        ];

        $gateway = $this->repository->update($id, $gatewayData);

        // Delete old credentials and store new ones
        $gateway->credentials()->delete();
        $this->storeCredentials($gateway->id, $data['credentials'] ?? []);

        return $gateway->load(['paymentGatewayType', 'credentials']);
    }

    public function deleteGateway($id): bool
    {
        $gateway = $this->repository->find($id);
        if (! $gateway) {
            return false;
        }

        $gateway->credentials()->delete();

        return $this->repository->delete($id) > 0;
    }

    public function testConnection(SchoolPaymentGateway $gateway): array
    {
        try {
            $gateway->load(['paymentGatewayType', 'credentials']);

            $gatewayInstance = $this->gatewayFactory->make(
                $gateway->paymentGatewayType->name,
                $gateway
            );

            $result = $gatewayInstance->checkTransactionStatus('test-transaction');

            if ($result && isset($result['success']) && ! $result['success']) {
                return [
                    'success' => false,
                    'message' => 'Gateway responded but with an error. Please check your credentials.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Gateway connection successful!',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    private function storeCredentials(string $gatewayId, array $credentials): void
    {
        foreach ($credentials as $key => $value) {
            if ($value !== null && $value !== '') {
                PaymentGatewayCredential::create([
                    'school_payment_gateway_id' => $gatewayId,
                    'credential_key' => $key,
                    'credential_value' => $value,
                    'is_sensitive' => true,
                ]);
            }
        }
    }
}
