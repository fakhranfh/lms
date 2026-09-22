<?php

namespace App\Services;

use App\Contracts\PaymentGateway as PaymentGatewayContract;
use App\Enums\XenditChannel;
use App\Models\PaymentGateway;
use App\Repositories\PaymentGateway\PaymentGatewayRepositoryInterface;
use App\Repositories\PaymentGatewayCredential\PaymentGatewayCredentialRepositoryInterface;
use App\Repositories\PaymentGatewayTestTransaction\PaymentGatewayTestTransactionRepositoryInterface;
use App\Repositories\PaymentGatewayType\PaymentGatewayTypeRepositoryInterface;
use App\Services\PaymentGateways\XenditGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentGatewayConfigService
{
    public function __construct(
        private readonly PaymentGatewayRepositoryInterface $repository,
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly PaymentGatewayTypeRepositoryInterface $gatewayTypeRepository,
        private readonly PaymentGatewayCredentialRepositoryInterface $credentialRepository,
        private readonly PaymentGatewayTestTransactionRepositoryInterface $testTransactionRepository,
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
        return $this->gatewayTypeRepository->getActive();
    }

    public function createGateway(array $data): PaymentGateway
    {
        $gatewayData = [
            'gateway_type_id' => $data['gateway_type_id'],
            'is_enabled' => $data['is_enabled'] ?? false,
            'is_sandbox_mode' => $data['is_sandbox_mode'] ?? true,
            'webhook_secret' => $data['webhook_secret'] ?? null,
            'enabled_channels' => $data['enabled_channels'] ?? [],
        ];

        $gateway = DB::transaction(function () use ($gatewayData, $data) {
            $gateway = $this->repository->create($gatewayData);

            $this->storeCredentials($gateway->id, $data['credentials'] ?? []);

            return $gateway;
        });

        return $gateway->load(['paymentGatewayType', 'credentials']);
    }

    public function updateGateway($id, array $data): PaymentGateway
    {
        $gatewayData = [
            'is_enabled' => $data['is_enabled'] ?? false,
            'is_sandbox_mode' => $data['is_sandbox_mode'] ?? true,
            'webhook_secret' => $data['webhook_secret'] ?? null,
            'enabled_channels' => $data['enabled_channels'] ?? [],
        ];

        $gateway = DB::transaction(function () use ($id, $gatewayData, $data) {
            $gateway = $this->repository->update($id, $gatewayData);

            // Credential fields are left blank in the edit form to mean
            // "keep current value" — only overwrite the ones actually submitted.
            $this->updateCredentials($gateway->id, $data['credentials'] ?? []);

            return $gateway;
        });

        return $gateway->load(['paymentGatewayType', 'credentials']);
    }

    public function deleteGateway($id): bool
    {
        $gateway = $this->repository->find($id);
        if (! $gateway) {
            return false;
        }

        return DB::transaction(function () use ($gateway, $id) {
            $this->credentialRepository->deleteForGateway($gateway->id);

            return $this->repository->delete($id) > 0;
        });
    }

    public function testConnection(PaymentGateway $gateway, ?string $channel = null): array
    {
        try {
            $gateway->load(['paymentGatewayType', 'credentials']);

            $gatewayInstance = $this->gatewayFactory->make(
                $gateway->paymentGatewayType->name,
                $gateway
            );

            // A specific channel was picked (from the "choose payment method"
            // modal) — always create a fresh dummy transaction for it so the
            // result page reflects that exact channel.
            if ($channel !== null) {
                return $this->createTestTransaction($gateway, $gatewayInstance, $channel);
            }

            $testTransaction = $this->testTransactionRepository->findForGateway($gateway->id);

            if ($testTransaction) {
                $result = $gatewayInstance->checkTransactionStatus($testTransaction->transaction_id);

                if ($result && ($result['success'] ?? false)) {
                    $this->testTransactionRepository->updateStatus($gateway->id, $result['status'] ?? $testTransaction->status);

                    return [
                        'success' => true,
                        'message' => 'Gateway connection successful!',
                    ];
                }
            }

            // No dummy transaction yet, or the stored one is no longer valid
            // (e.g. sandbox was reset) — create a fresh one to confirm the
            // gateway actually accepts these credentials.
            return $this->createTestTransaction($gateway, $gatewayInstance, $channel);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    private function createTestTransaction(PaymentGateway $gateway, PaymentGatewayContract $gatewayInstance, ?string $channel): array
    {
        $invoiceData = [
            'order_id' => 'connection-test-'.Str::uuid(),
            // Above Xendit's minimum for virtual account / retail channels.
            'amount' => 10000,
            'customer_email' => 'connection-test@example.com',
            'description' => 'Payment gateway connection test',
            // The admin gateway test-result page has been removed, so
            // redirect-based test channels (DANA, LinkAja, ...) fall back to
            // the generic app URL default.
            'success_return_url' => config('app.url'),
            'failure_return_url' => config('app.url'),
        ];

        if ($channel !== null) {
            $invoiceData['channel'] = $channel;
        }

        $invoice = $gatewayInstance->createInvoice($invoiceData);

        if (! ($invoice['success'] ?? false) || ! ($invoice['transaction_id'] ?? null)) {
            return [
                'success' => false,
                'message' => 'Gateway responded but with an error. Please check your credentials.',
            ];
        }

        $this->testTransactionRepository->storeForGateway($gateway->id, $invoice['transaction_id'], $invoice['status'] ?? null, $invoice);

        return [
            'success' => true,
            'message' => 'Gateway connection successful!',
        ];
    }

    /**
     * Simulate completion of the gateway's stored test-mode payment request.
     */
    public function simulateTestPayment(PaymentGateway $gateway): array
    {
        try {
            $gateway->load(['paymentGatewayType', 'credentials']);

            $testTransaction = $this->testTransactionRepository->findForGateway($gateway->id);

            if (! $testTransaction) {
                return [
                    'success' => false,
                    'message' => 'No test transaction found. Please run a connection test first.',
                ];
            }

            $gatewayInstance = $this->gatewayFactory->make(
                $gateway->paymentGatewayType->name,
                $gateway
            );

            if (! $gatewayInstance instanceof XenditGateway) {
                return [
                    'success' => false,
                    'message' => 'Payment simulation is only supported for Xendit gateways.',
                ];
            }

            $channelValue = $testTransaction->response['channel'] ?? null;
            $channel = $channelValue ? XenditChannel::from($channelValue) : null;

            if ($channel && ! $channel->supportsSimulation()) {
                return [
                    'success' => false,
                    'message' => "Xendit's payment simulation isn't available for {$channel->label()}. Approve it through the app/redirect instead.",
                ];
            }

            $amount = $testTransaction->response['amount'] ?? 10000;

            $result = $gatewayInstance->simulatePayment($testTransaction->transaction_id, $amount);

            if (! ($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Simulation failed: '.($result['error'] ?? 'Unknown error'),
                ];
            }

            if ($result['status'] ?? null) {
                $this->testTransactionRepository->updateStatus($gateway->id, $result['status']);
            }

            return [
                'success' => true,
                'message' => $result['message'] ?? 'Payment simulation triggered.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Simulation failed: '.$e->getMessage(),
            ];
        }
    }

    private function storeCredentials(string $gatewayId, array $credentials): void
    {
        foreach ($credentials as $key => $value) {
            if ($value !== null && $value !== '') {
                $this->credentialRepository->create([
                    'payment_gateway_id' => $gatewayId,
                    'credential_key' => $key,
                    'credential_value' => $value,
                    'is_sensitive' => true,
                ]);
            }
        }
    }

    private function updateCredentials(string $gatewayId, array $credentials): void
    {
        foreach ($credentials as $key => $value) {
            if ($value !== null && $value !== '') {
                $this->credentialRepository->updateOrCreate($gatewayId, $key, $value);
            }
        }
    }
}
