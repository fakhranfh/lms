<?php

namespace App\Livewire;

use App\Models\DemoLmsAccess;
use App\Models\PricingTier;
use App\Models\School;
use App\Services\PaymentGatewayRegistry;
use App\Services\TierChangeService;
use Livewire\Component;

class MySchools extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    public function changeTier(string $schoolId, string $tierId, TierChangeService $tierChangeService): mixed
    {
        $school = $this->authorizedSchool($schoolId);

        abort_if($this->isDemoMode($school), 403, 'Demo accounts cannot change pricing tiers.');

        $newTier = PricingTier::findOrFail($tierId);

        try {
            $transaction = $tierChangeService->initiateTierChange($school, $newTier);

            if ($transaction) {
                return $this->redirectRoute('school.payment.index', ['transaction' => $transaction]);
            }

            session()->flash('success', "Tier changed to {$newTier->name} successfully.");
        } catch (\Exception $e) {
            $this->addError('tier', $e->getMessage());
        }

        return null;
    }

    public function cancelTierChange(string $schoolId, TierChangeService $tierChangeService): void
    {
        $school = $this->authorizedSchool($schoolId);

        if ($tierChangeService->cancelTierChange($school)) {
            session()->flash('success', 'Tier change cancelled.');
        } else {
            $this->addError('tier', 'No pending tier change to cancel.');
        }
    }

    public function render(TierChangeService $tierChangeService, PaymentGatewayRegistry $gatewayRegistry)
    {
        $schools = auth()->user()->schools;

        $tierOverviews = $schools->mapWithKeys(
            fn (School $school) => [$school->id => $this->buildTierOverview($school, $tierChangeService, $gatewayRegistry)]
        );

        return view('livewire.my-schools', [
            'schools' => $schools,
            'tierOverviews' => $tierOverviews,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'My Schools'])
            ->section('app-content');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTierOverview(
        School $school,
        TierChangeService $tierChangeService,
        PaymentGatewayRegistry $gatewayRegistry
    ): array {
        $currentTier = $school->tier()->with('limits')->first();
        $availableTiers = PricingTier::with('limits')->where('is_active', true)->get();

        $upgradeTiers = $availableTiers->filter(
            fn (PricingTier $tier) => $tier->price > $currentTier->price
        );

        $downgradeTiers = $availableTiers->filter(
            fn (PricingTier $tier) => $tier->price < $currentTier->price
        );

        $prorations = [];
        $chargeAmounts = [];
        foreach ($availableTiers as $tier) {
            $prorations[$tier->id] = $tierChangeService->calculateProration($school, $tier);
            $chargeAmounts[$tier->id] = $tierChangeService->calculateChargeAmount($school, $tier);
        }

        $pendingTier = $school->schoolTiers()
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        return [
            'currentTier' => $currentTier,
            'upgradeTiers' => $upgradeTiers,
            'downgradeTiers' => $downgradeTiers,
            'prorations' => $prorations,
            'chargeAmounts' => $chargeAmounts,
            'pendingTier' => $pendingTier,
            'enabledGateways' => $gatewayRegistry->getEnabledGateways(),
            'isDemoMode' => $this->isDemoMode($school),
        ];
    }

    private function authorizedSchool(string $schoolId): School
    {
        $school = auth()->user()->schools->firstWhere('id', $schoolId);

        abort_unless($school !== null, 403);

        return $school;
    }

    private function isDemoMode(School $school): bool
    {
        return DemoLmsAccess::where('school_id', $school->id)
            ->where('user_id', auth()->id())
            ->where('expires_at', '>', now())
            ->exists();
    }
}
