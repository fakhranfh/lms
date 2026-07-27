<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\TierChangeJob;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use App\Models\SchoolTier;

class SubscriptionPaymentService
{
    public function __construct(
        private readonly PaymentGatewayFactory $factory
    ) {}

    public function createPaymentInvoice(SchoolTier $subscription, PaymentGateway $gateway, ?float $amountOverride = null): array
    {
        $gatewayInstance = $this->factory->make(
            $gateway->paymentGatewayType->name,
            $gateway
        );

        $amount = $amountOverride ?? $subscription->tier->price;

        return $gatewayInstance->createInvoice([
            'subscription_id' => $subscription->id,
            'school_id' => $subscription->school_id,
            'amount' => $amount,
            'currency' => $subscription->tier->currency,
            'description' => "Subscription: {$subscription->tier->name}",
        ]);
    }

    public function processWebhook(PaymentWebhook $webhook): bool
    {
        $schoolGateway = $webhook->paymentGateway;
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
            $subscription = $transaction->subscription;
            $billingPeriod = $subscription->tier->billing_period;
            $expiresAt = match ($billingPeriod->value) {
                'monthly' => now()->addMonth(),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'started_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            // Dispatch TierChangeJob if this is a tier change
            if (isset($transaction->metadata['change_type'])) {
                TierChangeJob::dispatch($transaction);
            }
        }
    }

    public function handleFailedPayment(PaymentTransaction $transaction): void
    {
        if ($transaction->subscription) {
            $transaction->subscription->update(['status' => SubscriptionStatus::Expired]);
        }
    }

    public function refundTransaction(PaymentTransaction $transaction, ?float $amount = null): bool
    {
        $schoolGateway = $transaction->paymentGateway;
        $gatewayInstance = $this->factory->make(
            $schoolGateway->paymentGatewayType->name,
            $schoolGateway
        );

        $refundAmount = $amount ?? $transaction->amount;
        if ($gatewayInstance->refund($transaction->transaction_id, $refundAmount)) {
            $transaction->update(['status' => PaymentStatus::Refunded]);

            return true;
        }

        return false;
    }
}
