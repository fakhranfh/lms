<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\TierChangeJob;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhook;
use App\Models\SchoolTier;
use App\Repositories\PaymentGateway\PaymentGatewayRepositoryInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionRepositoryInterface;

class SubscriptionPaymentService
{
    public function __construct(
        private readonly PaymentGatewayFactory $factory,
        private readonly PaymentGatewayRepositoryInterface $gatewayRepository,
        private readonly PaymentTransactionRepositoryInterface $paymentTransactionRepository,
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

    /**
     * Create the gateway invoice for a pending tier-change transaction (mirrors
     * SchoolService::initiateRegistrationPayment for the "existing school" case)
     * and record the resulting channel/instructions on the transaction.
     *
     * @return array<string, mixed>
     */
    public function initiateSubscriptionPayment(PaymentTransaction $transaction, ?string $channel = null): array
    {
        $gateway = $transaction->paymentGateway ?? $this->gatewayRepository->findFirstEnabled();

        if (! $gateway) {
            throw new \RuntimeException('No payment gateway configured.');
        }

        $gatewayInstance = $this->factory->make($gateway->paymentGatewayType->name, $gateway);

        $invoice = $gatewayInstance->createInvoice([
            'subscription_id' => $transaction->subscription_id,
            'school_id' => $transaction->school_id,
            'order_id' => $transaction->transaction_id,
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency,
            'channel' => $channel,
            'description' => "Subscription: {$transaction->tier_name}",
        ]);

        if ($invoice['success'] ?? false) {
            $this->paymentTransactionRepository->update($transaction->id, [
                'payment_gateway_id' => $gateway->id,
                'channel' => $channel ?? ($invoice['channel'] ?? null),
                'payment_instructions' => $invoice['payment_url'] ?? null,
                'transaction_id' => $invoice['transaction_id'] ?? $transaction->transaction_id,
            ]);
        }

        return $invoice;
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
            if ($transaction->change_type !== null) {
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
