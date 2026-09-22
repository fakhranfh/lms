<?php

namespace App\Http\Controllers;

use App\Http\Requests\TierChange\InitiateTierChangeRequest;
use App\Models\DemoLmsAccess;
use App\Models\PricingTier;
use App\Models\School;
use App\Services\PaymentGatewayRegistry;
use App\Services\TierChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TierChangeController extends Controller
{
    public function __construct(
        private readonly TierChangeService $tierChangeService,
        private readonly PaymentGatewayRegistry $gatewayRegistry,
    ) {}

    private function isDemoMode(School $school): bool
    {
        $user = auth()->user();

        return DemoLmsAccess::where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->exists();
    }

    private function denyUnlessCanManageTier(): void
    {
        abort_if(auth()->user()->hasRole(['Teacher', 'Student']), 403);
    }

    public function show(): View
    {
        $this->denyUnlessCanManageTier();

        $schoolId = auth()->user()->school_id;
        $school = School::findOrFail($schoolId);

        $isDemoMode = $this->isDemoMode($school);

        $currentTier = $school->tier()->with('limits')->first();
        $availableTiers = PricingTier::with('limits')->where('is_active', true)->get();

        $upgradeTiers = $availableTiers->filter(
            fn (PricingTier $tier) => $tier->price > $currentTier->price
        );

        $downgradeTiers = $availableTiers->filter(
            fn (PricingTier $tier) => $tier->price < $currentTier->price
        );

        $prorations = [];
        foreach ($availableTiers as $tier) {
            $prorations[$tier->id] = $this->tierChangeService->calculateProration($school, $tier);
        }

        $enabledGateways = $this->gatewayRegistry->getEnabledGateways();

        $pendingTier = $school->schoolTiers()
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        return view('tier-management.show', [
            'school' => $school,
            'currentTier' => $currentTier,
            'upgradeTiers' => $upgradeTiers,
            'downgradeTiers' => $downgradeTiers,
            'prorations' => $prorations,
            'enabledGateways' => $enabledGateways,
            'pendingTier' => $pendingTier,
            'isDemoMode' => $isDemoMode,
        ]);
    }

    public function initiate(InitiateTierChangeRequest $request): RedirectResponse
    {
        $this->denyUnlessCanManageTier();

        $schoolId = auth()->user()->school_id;
        $school = School::findOrFail($schoolId);

        abort_if($this->isDemoMode($school), 403, 'Demo accounts cannot change pricing tiers.');

        $newTier = PricingTier::findOrFail($request->input('tier_id'));

        $gatewayName = $request->input('gateway_name');

        try {
            $transaction = $this->tierChangeService->initiateTierChange(
                $school,
                $newTier,
                $gatewayName
            );

            if ($transaction) {
                // TODO: the school-payment checkout flow was removed
                // (school.payment.* routes / SchoolPaymentController).
                // TierChangeService::initiateTierChange() still creates a
                // pending PaymentTransaction for paid upgrades and returns
                // it here, expecting the caller to redirect to the payment
                // page to collect payment. That page no longer exists, so
                // paid upgrades are refused until a replacement payment
                // flow is decided. The pending transaction it already
                // created is left as-is (visible/cancellable from My
                // Transactions).
                return back()->withErrors(['error' => 'Upgrading to a paid tier is currently unavailable.']);
            }

            return back()->with('success', "Tier changed to {$newTier->name} successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(): RedirectResponse
    {
        $this->denyUnlessCanManageTier();

        $schoolId = auth()->user()->school_id;
        $school = School::findOrFail($schoolId);

        if ($this->tierChangeService->cancelTierChange($school)) {
            return back()->with('success', 'Tier change cancelled.');
        }

        return back()->withErrors(['error' => 'No pending tier change to cancel.']);
    }
}
