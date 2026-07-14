<?php

namespace App\Jobs;

use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use App\Services\SubscriptionPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PaymentWebhook $webhook
    ) {}

    public function handle(SubscriptionPaymentService $paymentService): void
    {
        if ($this->webhook->processed) {
            return;
        }

        try {
            $payload = json_decode($this->webhook->payload, true);
            $transactionId = $this->extractTransactionId($payload);

            if ($transactionId) {
                $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
                if ($transaction) {
                    $transaction->update([
                        'status' => $this->extractTransactionStatus($payload),
                        'metadata' => $payload,
                    ]);

                    if ($transaction->status === 'completed') {
                        $paymentService->completeSubscription($transaction);
                    } elseif ($transaction->status === 'failed') {
                        $paymentService->handleFailedPayment($transaction);
                    }
                }
            }

            $paymentService->processWebhook($this->webhook);
        } catch (\Exception) {
            $this->release(60);
        }
    }

    private function extractTransactionId(array $payload): ?string
    {
        return $payload['transaction_id'] ?? $payload['id'] ?? null;
    }

    private function extractTransactionStatus(array $payload): string
    {
        if (isset($payload['transaction_status'])) {
            return match (strtolower($payload['transaction_status'])) {
                'capture' => 'completed',
                'settlement' => 'completed',
                'pending' => 'pending',
                'deny' => 'failed',
                'cancel' => 'failed',
                'expire' => 'failed',
                default => 'pending',
            };
        }

        if (isset($payload['status'])) {
            return match (strtolower($payload['status'])) {
                'paid' => 'completed',
                'pending' => 'pending',
                'expired' => 'failed',
                default => 'pending',
            };
        }

        return 'pending';
    }
}
