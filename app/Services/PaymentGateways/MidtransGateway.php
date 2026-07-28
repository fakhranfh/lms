<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\PaymentGateway as PaymentGatewayModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class MidtransGateway implements PaymentGateway
{
    private string $baseUrl;

    private string $serverKey;

    public function __construct(
        private readonly PaymentGatewayModel $config,
        private readonly array $credentials,
    ) {
        $this->serverKey = $credentials['server_key'] ?? '';
        $this->baseUrl = $this->config->is_sandbox_mode
            ? 'https://app.sandbox.midtrans.com/api/v2'
            : 'https://app.midtrans.com/api/v2';
    }

    public function createInvoice(array $data): array
    {
        try {
            $payload = [
                'transaction_details' => [
                    'order_id' => $data['order_id'] ?? uniqid('ord-'),
                    'gross_amount' => (int) ($data['amount'] * 100) / 100,
                ],
                'customer_details' => [
                    'email' => $data['customer_email'] ?? '',
                    'first_name' => $data['customer_name'] ?? 'Customer',
                ],
            ];

            if (isset($data['item_details'])) {
                $payload['item_details'] = $data['item_details'];
            }

            if (isset($data['metadata'])) {
                $payload['custom_field1'] = json_encode($data['metadata']);
            }

            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->retry(3, 100)
                ->post("{$this->baseUrl}/charge", $payload)
                ->throw()
                ->json();

            return [
                'success' => $response['status_code'] === '201' || $response['status_code'] === 201,
                'transaction_id' => $response['transaction_id'] ?? null,
                'order_id' => $response['order_id'] ?? $payload['transaction_details']['order_id'],
                'status' => strtolower($response['transaction_status'] ?? 'pending'),
                'payment_url' => $response['redirect_url'] ?? null,
                'amount' => (int) ($response['gross_amount'] ?? $payload['transaction_details']['gross_amount']),
                'currency' => $response['currency'] ?? 'IDR',
            ];
        } catch (RequestException|ConnectionException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e instanceof RequestException ? $e->response->status() : null,
            ];
        }
    }

    public function handleWebhook(array $payload): bool
    {
        try {
            if (! $this->verifyWebhookSignature($payload)) {
                return false;
            }

            $transactionId = $payload['transaction_id'] ?? null;
            $orderId = $payload['order_id'] ?? null;
            $status = $payload['transaction_status'] ?? null;

            if (! $transactionId || ! $status) {
                return false;
            }

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function checkTransactionStatus(string $transactionId): array
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->get("{$this->baseUrl}/{$transactionId}/status")
                ->throw()
                ->json();

            return [
                'success' => true,
                'transaction_id' => $response['transaction_id'] ?? $transactionId,
                'status' => strtolower($response['transaction_status'] ?? 'unknown'),
                'amount' => $response['gross_amount'] ?? null,
                'currency' => $response['currency'] ?? 'IDR',
                'payment_method' => $response['payment_type'] ?? null,
            ];
        } catch (RequestException|ConnectionException $e) {
            return [
                'success' => false,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'status_code' => $e instanceof RequestException ? $e->response->status() : null,
            ];
        }
    }

    public function refund(string $transactionId, float $amount): bool
    {
        try {
            $payload = [
                'refund_key' => "refund-{$transactionId}-".time(),
            ];

            if ($amount > 0) {
                $payload['amount'] = (int) ($amount * 100) / 100;
            }

            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->post("{$this->baseUrl}/{$transactionId}/refund", $payload)
                ->throw()
                ->json();

            return isset($response['status_code']) &&
                ($response['status_code'] === '200' || $response['status_code'] === 200);
        } catch (RequestException|ConnectionException) {
            return false;
        }
    }

    public function extractWebhookTransactionId(array $payload): ?string
    {
        return $payload['transaction_id'] ?? null;
    }

    public function extractWebhookStatus(array $payload): PaymentStatus
    {
        $status = strtolower($payload['transaction_status'] ?? '');

        return match ($status) {
            'capture', 'settlement' => PaymentStatus::Completed,
            'pending' => PaymentStatus::Pending,
            'deny', 'cancel', 'expire' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }

    private function verifyWebhookSignature(array $payload): bool
    {
        $serverKey = $this->serverKey;
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signature = $payload['signature'] ?? '';

        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($signatureKey, $signature);
    }
}
