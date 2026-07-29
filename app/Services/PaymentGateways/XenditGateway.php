<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\XenditChannel;
use App\Models\PaymentGateway as PaymentGatewayModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class XenditGateway implements PaymentGateway
{
    private string $apiKey;

    // Xendit has no separate sandbox host — test vs live mode is determined
    // by which kind of API key (test_/live_) is used against this same URL.
    private string $baseUrl = 'https://api.xendit.co';

    public function __construct(
        private readonly PaymentGatewayModel $config,
        private readonly array $credentials,
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
    }

    public function createInvoice(array $data): array
    {
        try {
            $channel = $this->resolveChannel($data['channel'] ?? null);

            $payload = [
                'reference_id' => $data['order_id'] ?? uniqid('inv-'),
                'type' => $channel->requestType(),
                'country' => 'ID',
                'currency' => $data['currency'] ?? 'IDR',
                'request_amount' => (int) $data['amount'],
                'channel_code' => $channel->xenditChannelCode(),
                'channel_properties' => $channel->buildChannelProperties($data),
                'description' => $data['description'] ?? 'Payment for subscription',
            ];

            if (isset($data['items'])) {
                $payload['items'] = $data['items'];
            }

            if (isset($data['metadata'])) {
                $payload['metadata'] = $data['metadata'];
            }

            $response = Http::withBasicAuth($this->apiKey, '')
                ->withHeaders(['api-version' => '2024-11-11'])
                ->timeout(30)
                ->retry(3, 100)
                ->post("{$this->baseUrl}/v3/payment_requests", $payload)
                ->throw()
                ->json();

            return [
                'success' => true,
                'transaction_id' => $response['payment_request_id'] ?? null,
                'order_id' => $response['reference_id'] ?? $payload['reference_id'],
                'status' => strtolower($response['status'] ?? 'pending'),
                'payment_url' => $this->extractCustomerAction($response),
                'amount' => (int) ($response['request_amount'] ?? $payload['request_amount']),
                'currency' => $response['currency'] ?? 'IDR',
                'channel' => $channel->value,
            ];
        } catch (RequestException|ConnectionException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e instanceof RequestException ? $e->response->status() : null,
            ];
        }
    }

    /**
     * The callback-token signature is verified synchronously in
     * PaymentWebhookService before the webhook is even queued (a queued job
     * has no access to the original request's headers), so this only needs
     * to sanity-check the payload shape.
     */
    public function handleWebhook(array $payload): bool
    {
        try {
            $data = $this->webhookData($payload);

            if (! ($data['payment_request_id'] ?? $data['id'] ?? null) || ! ($data['status'] ?? null)) {
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
            $response = Http::withBasicAuth($this->apiKey, '')
                ->withHeaders(['api-version' => '2024-11-11'])
                ->timeout(30)
                ->get("{$this->baseUrl}/v3/payment_requests/{$transactionId}")
                ->throw()
                ->json();

            return [
                'success' => true,
                'transaction_id' => $response['payment_request_id'] ?? $transactionId,
                'status' => strtolower($response['status'] ?? 'pending'),
                'amount' => $response['request_amount'] ?? null,
                'currency' => $response['currency'] ?? 'IDR',
                'payment_method' => $response['channel_code'] ?? null,
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

    /**
     * Simulate completion of a test-mode payment request.
     * Only works against payment requests created with a test API key.
     */
    public function simulatePayment(string $transactionId, float $amount): array
    {
        try {
            $payload = ['amount' => (int) $amount];

            $response = Http::withBasicAuth($this->apiKey, '')
                ->withHeaders(['api-version' => '2024-11-11'])
                ->timeout(30)
                ->post("{$this->baseUrl}/v3/payment_requests/{$transactionId}/simulate", $payload)
                ->throw()
                ->json();

            return [
                'success' => true,
                'status' => strtolower($response['status'] ?? 'pending'),
                'message' => $response['message'] ?? 'Payment simulation triggered.',
            ];
        } catch (RequestException|ConnectionException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e instanceof RequestException ? $e->response->status() : null,
            ];
        }
    }

    public function refund(string $transactionId, float $amount): bool
    {
        try {
            $payload = [
                'payment_request_id' => $transactionId,
                'amount' => (int) $amount,
            ];

            $response = Http::withBasicAuth($this->apiKey, '')
                ->timeout(30)
                ->post("{$this->baseUrl}/refunds", $payload)
                ->throw()
                ->json();

            return isset($response['id']);
        } catch (RequestException|ConnectionException) {
            return false;
        }
    }

    public function extractWebhookTransactionId(array $payload): ?string
    {
        $data = $this->webhookData($payload);

        return $data['payment_request_id'] ?? $data['id'] ?? null;
    }

    public function extractWebhookStatus(array $payload): PaymentStatus
    {
        $data = $this->webhookData($payload);
        $status = strtolower($data['status'] ?? '');

        return match ($status) {
            'succeeded', 'accepting_payments', 'paid' => PaymentStatus::Completed,
            'pending', 'requires_action', 'authorized' => PaymentStatus::Pending,
            'failed', 'expired', 'canceled' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }

    /**
     * v3 webhooks wrap the payment request in a "data" envelope:
     * {"event": "payment.succeeded", "data": {"payment_request_id": ..., "status": ...}}
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function webhookData(array $payload): array
    {
        return $payload['data'] ?? $payload;
    }

    /**
     * Resolve which channel to create the payment request against.
     * Falls back to the gateway's first enabled channel, then QRIS.
     */
    private function resolveChannel(XenditChannel|string|null $channel): XenditChannel
    {
        if ($channel instanceof XenditChannel) {
            return $channel;
        }

        if (is_string($channel)) {
            return XenditChannel::from($channel);
        }

        $enabledChannels = $this->config->enabled_channels ?? [];

        if (! empty($enabledChannels)) {
            return XenditChannel::from($enabledChannels[0]);
        }

        return XenditChannel::Qris;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractCustomerAction(array $response): ?string
    {
        foreach ($response['actions'] ?? [] as $action) {
            if (in_array($action['descriptor'] ?? null, ['WEB_URL', 'DEEPLINK_URL', 'QR_STRING', 'VIRTUAL_ACCOUNT_NUMBER', 'PAYMENT_CODE'], true)) {
                return $action['value'] ?? null;
            }
        }

        return null;
    }
}
