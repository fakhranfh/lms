<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use App\Repositories\PaymentGatewayTestTransaction\PaymentGatewayTestTransactionRepositoryInterface;
use App\Services\PaymentGatewayFactory;
use App\Services\SubscriptionPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PaymentWebhook $webhook
    ) {}

    public function handle(
        SubscriptionPaymentService $paymentService,
        PaymentGatewayFactory $gatewayFactory,
        PaymentGatewayTestTransactionRepositoryInterface $testTransactionRepository
    ): void {
        if ($this->webhook->processed) {
            return;
        }

        try {
            $payload = json_decode($this->webhook->payload, true);

            $gateway = $this->webhook->paymentGateway;
            $gatewayInstance = $gatewayFactory->make($gateway->paymentGatewayType->name, $gateway);

            $transactionId = $gatewayInstance->extractWebhookTransactionId($payload);

            if ($transactionId) {
                $status = $gatewayInstance->extractWebhookStatus($payload);

                $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
                if ($transaction) {
                    $transaction->update([
                        'status' => $status,
                        'metadata' => $payload,
                    ]);

                    if ($transaction->status === PaymentStatus::Completed) {
                        $paymentService->completeSubscription($transaction);
                    } elseif ($transaction->status === PaymentStatus::Failed) {
                        $paymentService->handleFailedPayment($transaction);
                    }
                }

                // Xendit's /simulate endpoint always responds with PENDING —
                // the real outcome only arrives later via this webhook, so
                // the admin "test payment" result page needs updating here too.
                $testTransactionRepository->updateStatusByTransactionId($transactionId, $status->value);
            }

            $paymentService->processWebhook($this->webhook);
        } catch (\Exception) {
            $this->release(60);
        }
    }
}
