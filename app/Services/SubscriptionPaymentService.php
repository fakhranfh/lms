<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use App\Models\SchoolPaymentGateway;
use App\Models\SchoolTier;

class SubscriptionPaymentService
{
    public function __construct(
        private readonly PaymentGatewayFactory $factory
    ) {}

    public function createPaymentInvoice(SchoolTier $subscription, SchoolPaymentGateway $gateway): array
    {
        $gatewayInstance = $this->factory->make(
            $gateway->paymentGatewayType->name,
            $gateway
        );

        return $gatewayInstance->createInvoice([
            'subscription_id' => $subscription->id,
            'school_id' => $subscription->school_id,
            'amount' => $subscription->tier->price,
            'currency' => $subscription->tier->currency,
            'description' => "Subscription: {$subscription->tier->name}",
        ]);
    }

    public function processWebhook(PaymentWebhook $webhook): bool
    {
        $schoolGateway = $webhook->schoolPaymentGateway;
        $gatewayInstance = $this->factory->make(
            $schoolGateway->paymentGatewayType->name,
            $schoolGateway
        );

        $payload = json_decode($webhook->payload, true);
        if ($gatewayInstance->handleWebhook($payload)) {
            $webhook->update(['processed' => true, 'processed_at' => now()]);

            return true;
        }

        return false;
    }

    public function completeSubscription(PaymentTransaction $transaction): void
    {
        if ($transaction->subscription) {
            $transaction->subscription->update([
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->addMonth(),
            ]);
        }
    }

    public function handleFailedPayment(PaymentTransaction $transaction): void
    {
        if ($transaction->subscription) {
            $transaction->subscription->update(['status' => 'expired']);
        }
    }

    public function refundTransaction(PaymentTransaction $transaction, ?float $amount = null): bool
    {
        $schoolGateway = $transaction->schoolPaymentGateway;
        $gatewayInstance = $this->factory->make(
            $schoolGateway->paymentGatewayType->name,
            $schoolGateway
        );

        $refundAmount = $amount ?? $transaction->amount;
        if ($gatewayInstance->refund($transaction->transaction_id, $refundAmount)) {
            $transaction->update(['status' => 'refunded']);

            return true;
        }

        return false;
    }
}
