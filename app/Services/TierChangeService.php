<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\TierChangeType;
use App\Exceptions\TierChangeInProgressException;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\PricingTier;
use App\Models\School;
use App\Repositories\PaymentGateway\PaymentGatewayRepositoryInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionRepositoryInterface;
use App\Repositories\PricingTier\PricingTierRepositoryInterface;
use App\Repositories\School\SchoolRepositoryInterface;
use App\Repositories\SchoolTier\SchoolTierRepositoryInterface;
use App\Repositories\TierChange\TierChangeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TierChangeService
{
    public function __construct(
        private readonly SubscriptionPaymentService $paymentService,
        private readonly SchoolRepositoryInterface $schoolRepository,
        private readonly PricingTierRepositoryInterface $pricingTierRepository,
        private readonly SchoolTierRepositoryInterface $schoolTierRepository,
        private readonly TierChangeRepositoryInterface $tierChangeRepository,
        private readonly PaymentTransactionRepositoryInterface $paymentTransactionRepository,
        private readonly PaymentGatewayRepositoryInterface $gatewayRepository,
    ) {}

    public function canUpgrade(School $school, PricingTier $newTier): bool
    {
        return $newTier->is_active && $newTier->price > $school->tier->price;
    }

    public function canDowngrade(School $school, PricingTier $newTier): bool
    {
        return $newTier->is_active && $newTier->price < $school->tier->price;
    }

    public function calculateProration(School $school, PricingTier $newTier): float
    {
        $currentTier = $school->tier;
        if (! $currentTier || ! $school->getCurrentSchoolTier()) {
            return 0.0;
        }

        $currentSubscription = $school->getCurrentSchoolTier();
        if (! $currentSubscription->expires_at) {
            return 0.0;
        }

        $remainingDays = Carbon::now()->diffInDays($currentSubscription->expires_at, false);
        if ($remainingDays <= 0) {
            return 0.0;
        }

        $billingPeriod = $newTier->billing_period;
        $daysInPeriod = $billingPeriod->value === 'monthly' ? 30 : 365;

        $oldDailyRate = $currentTier->price / $daysInPeriod;
        $newDailyRate = $newTier->price / $daysInPeriod;

        return ($newDailyRate - $oldDailyRate) * $remainingDays;
    }

    public function initiateTierChange(
        School $school,
        PricingTier $newTier,
        ?string $gatewayName = null
    ): ?array {
        // Check if a tier change is already in progress
        if ($this->schoolTierRepository->hasPendingForSchool($school->id)) {
            throw new TierChangeInProgressException;
        }

        $currentTier = $school->tier;
        $oldTierId = $currentTier->id;
        $proration = $this->calculateProration($school, $newTier);
        $isUpgrade = $newTier->price > $currentTier->price;
        $isPaid = $newTier->price > 0;

        // Immediate application for downgrades or free tiers
        if (! $isUpgrade || ! $isPaid) {
            return $this->applyImmediateChange($school, $newTier, $oldTierId, $proration);
        }

        // Payment-gated upgrade
        $gateway = $this->resolveGateway($school, $gatewayName);

        $amount = max(0, $proration);

        $schoolTier = DB::transaction(function () use ($school, $newTier, $gateway, $oldTierId, $proration, $amount) {
            $schoolTier = $this->schoolTierRepository->create([
                'school_id' => $school->id,
                'tier_id' => $newTier->id,
                'status' => SubscriptionStatus::Pending,
                'started_at' => now(),
                'expires_at' => null,
                'renewal_date' => null,
                'auto_renew' => true,
                'payment_method' => $gateway->paymentGatewayType->name,
            ]);

            $this->paymentTransactionRepository->create([
                'school_id' => $school->id,
                'subscription_id' => $schoolTier->id,
                'payment_gateway_id' => $gateway->id,
                'transaction_id' => 'temp-'.uniqid(),
                'amount' => $amount,
                'currency' => $newTier->currency,
                'status' => 'pending',
                'metadata' => [
                    'from_tier_id' => $oldTierId,
                    'change_type' => TierChangeType::Upgrade->value,
                    'proration_amount' => $proration,
                ],
            ]);

            return $schoolTier;
        });

        $invoice = $this->paymentService->createPaymentInvoice(
            $schoolTier,
            $gateway,
            $amount > 0 ? $amount : null
        );

        return $invoice;
    }

    public function cancelTierChange(School $school): bool
    {
        $pendingTier = $this->schoolTierRepository->findPendingForSchool($school->id);

        if (! $pendingTier) {
            return false;
        }

        DB::transaction(function () use ($pendingTier): void {
            $this->schoolTierRepository->update($pendingTier->id, ['status' => SubscriptionStatus::Expired]);

            $transaction = $this->paymentTransactionRepository->findPendingBySubscriptionId($pendingTier->id);

            if ($transaction) {
                $this->paymentTransactionRepository->update($transaction->id, ['status' => 'failed']);
            }
        });

        return true;
    }

    public function finalizeTierChange(PaymentTransaction $transaction): void
    {
        $schoolTier = $transaction->subscription;
        if (! $schoolTier) {
            return;
        }

        $school = $schoolTier->school;
        $oldTierId = $school->tier_id;
        $newTierId = $schoolTier->tier_id;

        // Extract proration from metadata
        $proration = $transaction->metadata['proration_amount'] ?? 0;
        $changeType = match ($transaction->metadata['change_type'] ?? null) {
            TierChangeType::Upgrade->value => TierChangeType::Upgrade,
            TierChangeType::Downgrade->value => TierChangeType::Downgrade,
            default => TierChangeType::Upgrade,
        };

        DB::transaction(function () use ($school, $schoolTier, $oldTierId, $newTierId, $proration, $changeType): void {
            // Update school tier
            $this->schoolRepository->update($school->id, ['tier_id' => $newTierId]);

            // Update SchoolTier status
            $this->schoolTierRepository->update($schoolTier->id, ['status' => SubscriptionStatus::Active]);

            // Create audit trail
            $this->tierChangeRepository->create([
                'school_tier_id' => $schoolTier->id,
                'from_tier_id' => $oldTierId,
                'to_tier_id' => $newTierId,
                'change_type' => $changeType->value,
                'proration_amount' => $proration,
                'changed_at' => now(),
            ]);
        });
    }

    private function applyImmediateChange(
        School $school,
        PricingTier $newTier,
        int $oldTierId,
        float $proration
    ): ?array {
        $oldTier = $this->pricingTierRepository->find($oldTierId);

        $newPrice = (float) $newTier->price;
        $oldPrice = (float) $oldTier->price;

        $changeType = ($newPrice < $oldPrice)
            ? TierChangeType::Downgrade
            : TierChangeType::Initial;

        DB::transaction(function () use ($school, $newTier, $oldTierId, $proration, $changeType): void {
            // Update school tier
            $this->schoolRepository->update($school->id, ['tier_id' => $newTier->id]);

            // Create SchoolTier record
            $schoolTier = $this->schoolTierRepository->create([
                'school_id' => $school->id,
                'tier_id' => $newTier->id,
                'status' => SubscriptionStatus::Active,
                'started_at' => now(),
                'expires_at' => $newTier->price > 0 ? now()->addMonth() : null,
                'renewal_date' => null,
                'auto_renew' => true,
                'payment_method' => null,
            ]);

            // Create audit trail
            $this->tierChangeRepository->create([
                'school_tier_id' => $schoolTier->id,
                'from_tier_id' => $oldTierId,
                'to_tier_id' => $newTier->id,
                'change_type' => $changeType->value,
                'proration_amount' => $proration,
                'changed_at' => now(),
            ]);
        });

        // Best-effort refund for downgrade
        if ($changeType === TierChangeType::Downgrade && $proration < 0) {
            $this->attemptRefund($school, abs($proration));
        }

        return null;
    }

    private function resolveGateway(School $school, ?string $gatewayName): PaymentGateway
    {
        if ($gatewayName) {
            $gateway = $this->gatewayRepository->findEnabledForSchoolByGatewayName($school->id, $gatewayName);

            if ($gateway) {
                return $gateway;
            }
        }

        // Fall back to first enabled gateway
        $gateway = $this->gatewayRepository->findFirstEnabledForSchool($school->id);

        if (! $gateway) {
            throw new \RuntimeException('No payment gateway configured for school.');
        }

        return $gateway;
    }

    private function attemptRefund(School $school, float $amount): void
    {
        $lastTransaction = $this->paymentTransactionRepository->findLatestCompletedForSchool($school->id);

        if ($lastTransaction) {
            $this->paymentService->refundTransaction($lastTransaction, $amount);
        }
    }
}
