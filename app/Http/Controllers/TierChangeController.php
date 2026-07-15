<?php

namespace App\Http\Controllers;

use App\Http\Requests\TierChange\InitiateTierChangeRequest;
use App\Models\PricingTier;
use App\Models\School;
use App\Services\TierChangeService;
use App\Support\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TierChangeController extends Controller
{
    public function __construct(
        private readonly TierChangeService $tierChangeService,
        private readonly CurrentSchool $currentSchool
    ) {}

    public function show(): View
    {
        $schoolId = $this->currentSchool->getSchoolId();
        $school = School::findOrFail($schoolId);

        $currentTier = $school->tier;
        $availableTiers = PricingTier::where('is_active', true)->get();

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

        $enabledGateways = $school->paymentGateways()->where('is_enabled', true)->get();

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
        ]);
    }

    public function initiate(InitiateTierChangeRequest $request): RedirectResponse
    {
        $schoolId = $this->currentSchool->getSchoolId();
        $school = School::findOrFail($schoolId);
        $newTier = PricingTier::findOrFail($request->input('tier_id'));

        $gatewayName = $request->input('gateway_name');

        try {
            $invoice = $this->tierChangeService->initiateTierChange(
                $school,
                $newTier,
                $gatewayName
            );

            if ($invoice && isset($invoice['redirect_url'])) {
                return redirect($invoice['redirect_url']);
            }

            return back()->with('success', "Tier changed to {$newTier->name} successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(): RedirectResponse
    {
        $schoolId = $this->currentSchool->getSchoolId();
        $school = School::findOrFail($schoolId);

        if ($this->tierChangeService->cancelTierChange($school)) {
            return back()->with('success', 'Tier change cancelled.');
        }

        return back()->withErrors(['error' => 'No pending tier change to cancel.']);
    }
}
