<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGateway;
use App\Models\SchoolPaymentGateway;
use App\Services\CredentialEncryption;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class XenditGateway implements PaymentGateway
{
    private string $apiKey;

    private string $baseUrl = 'https://api.xendit.co';

    public function __construct(
        private readonly SchoolPaymentGateway $config,
        private readonly array $credentials,
        private readonly CredentialEncryption $credentialEncryption,
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        if ($this->config->is_sandbox_mode) {
            $this->baseUrl = 'https://api.sandbox.xendit.co';
        }
    }

    public function createInvoice(array $data): array
    {
        try {
            $payload = [
                'external_id' => $data['order_id'] ?? uniqid('inv-'),
                'amount' => (int) $data['amount'],
                'payer_email' => $data['customer_email'] ?? 'customer@example.com',
                'description' => $data['description'] ?? 'Payment for subscription',
            ];

            if (isset($data['customer_name'])) {
                $payload['customer_name'] = $data['customer_name'];
            }

            if (isset($data['due_date'])) {
                $payload['due_date'] = $data['due_date'];
            }

            if (isset($data['items'])) {
                $payload['items'] = $data['items'];
            }

            if (isset($data['metadata'])) {
                $payload['metadata'] = $data['metadata'];
            }

            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->retry(3, 100)
                ->post("{$this->baseUrl}/v2/invoices", $payload)
                ->throw()
                ->json();

            return [
                'success' => true,
                'transaction_id' => $response['id'] ?? null,
                'order_id' => $response['external_id'] ?? $payload['external_id'],
                'status' => $response['status'] ?? 'PENDING',
                'payment_url' => $response['invoice_url'] ?? null,
                'amount' => $response['amount'] ?? $payload['amount'],
                'currency' => $response['currency'] ?? 'IDR',
                'expiry_date' => $response['expiry_date'] ?? null,
                'raw_response' => $response,
            ];
        } catch (RequestException|ConnectionException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e->response?->status(),
                'response_body' => $e->response?->json(),
            ];
        }
    }

    public function handleWebhook(array $payload): bool
    {
        try {
            if (! $this->verifyWebhookSignature($payload)) {
                return false;
            }

            $invoiceId = $payload['id'] ?? null;
            $status = $payload['status'] ?? null;

            if (! $invoiceId || ! $status) {
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
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->get("{$this->baseUrl}/v2/invoices/{$transactionId}")
                ->throw()
                ->json();

            return [
                'success' => true,
                'transaction_id' => $response['id'] ?? $transactionId,
                'status' => $response['status'] ?? 'PENDING',
                'amount' => $response['amount'] ?? null,
                'paid_amount' => $response['paid_amount'] ?? 0,
                'currency' => $response['currency'] ?? 'IDR',
                'payment_method' => $response['payment_method'] ?? null,
                'raw_response' => $response,
            ];
        } catch (RequestException|ConnectionException $e) {
            return [
                'success' => false,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'status_code' => $e->response?->status(),
            ];
        }
    }

    public function refund(string $transactionId, float $amount): bool
    {
        try {
            $payload = [
                'amount' => (int) $amount,
            ];

            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/v2/invoices/{$transactionId}/refunds", $payload)
                ->throw()
                ->json();

            return isset($response['id']);
        } catch (RequestException|ConnectionException) {
            return false;
        }
    }

    private function verifyWebhookSignature(array $payload): bool
    {
        $xInvoiceToken = request()->header('X-Callback-Token');

        if (! $xInvoiceToken) {
            return false;
        }

        $callbackToken = $this->config->webhook_secret ?? '';

        return hash_equals($xInvoiceToken, $callbackToken);
    }
}
