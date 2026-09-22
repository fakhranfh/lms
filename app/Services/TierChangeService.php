<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\TierChangeType;
use App\Exceptions\TierChangeInProgressException;
use App\Models\PricingTier;
use App\Models\School;
use App\Repositories\PricingTier\PricingTierRepositoryInterface;
use App\Repositories\School\SchoolRepositoryInterface;
use App\Repositories\SchoolTier\SchoolTierRepositoryInterface;
use App\Repositories\TierChange\TierChangeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TierChangeService
{
    public function __construct(
        private readonly SchoolRepositoryInterface $schoolRepository,
        private readonly PricingTierRepositoryInterface $pricingTierRepository,
        private readonly SchoolTierRepositoryInterface $schoolTierRepository,
        private readonly TierChangeRepositoryInterface $tierChangeRepository,
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

    /**
     * The actual amount that would be charged for an upgrade. Paid-tier
     * upgrades are currently unavailable (see initiateTierChange()), so this
     * is only used for display purposes (e.g. showing a prospective charge).
     */
    public function calculateChargeAmount(School $school, PricingTier $newTier): float
    {
        $proration = $this->calculateProration($school, $newTier);

        return $proration > 0 ? $proration : (float) $newTier->price;
    }

    /**
     * Start a tier change. Downgrades and free tiers apply immediately.
     *
     * Paid upgrades are not supported: the payment gateway subsystem that
     * used to collect payment for them was removed, and no replacement
     * payment flow exists yet. Callers should catch this and surface it to
     * the user.
     *
     * @throws TierChangeInProgressException
     * @throws \RuntimeException when the requested change is a paid upgrade
     */
    public function initiateTierChange(School $school, PricingTier $newTier): void
    {
        // Check if a tier change is already in progress
        if ($this->schoolTierRepository->hasPendingForSchool($school->id)) {
            throw new TierChangeInProgressException;
        }

        $currentTier = $school->tier;
        $oldTierId = $currentTier->id;
        $proration = $this->calculateProration($school, $newTier);
        $isUpgrade = $newTier->price > $currentTier->price;
        $isPaid = $newTier->price > 0;

        if ($isUpgrade && $isPaid) {
            throw new \RuntimeException('Upgrading to a paid tier is currently unavailable.');
        }

        $this->applyImmediateChange($school, $newTier, $oldTierId, $proration);
    }

    public function cancelTierChange(School $school): bool
    {
        $pendingTier = $this->schoolTierRepository->findPendingForSchool($school->id);

        if (! $pendingTier) {
            return false;
        }

        $this->schoolTierRepository->update($pendingTier->id, ['status' => SubscriptionStatus::Expired]);

        return true;
    }

    private function applyImmediateChange(
        School $school,
        PricingTier $newTier,
        int $oldTierId,
        float $proration
    ): void {
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
    }
}
