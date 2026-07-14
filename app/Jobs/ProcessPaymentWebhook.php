<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
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

                    if ($transaction->status === PaymentStatus::Completed) {
                        $paymentService->completeSubscription($transaction);
                    } elseif ($transaction->status === PaymentStatus::Failed) {
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

    private function extractTransactionStatus(array $payload): PaymentStatus
    {
        if (isset($payload['transaction_status'])) {
            return match (strtolower($payload['transaction_status'])) {
                'capture' => PaymentStatus::Completed,
                'settlement' => PaymentStatus::Completed,
                'pending' => PaymentStatus::Pending,
                'deny' => PaymentStatus::Failed,
                'cancel' => PaymentStatus::Failed,
                'expire' => PaymentStatus::Failed,
                default => PaymentStatus::Pending,
            };
        }

        if (isset($payload['status'])) {
            return match (strtolower($payload['status'])) {
                'paid' => PaymentStatus::Completed,
                'pending' => PaymentStatus::Pending,
                'expired' => PaymentStatus::Failed,
                default => PaymentStatus::Pending,
            };
        }

        return PaymentStatus::Pending;
    }
}
