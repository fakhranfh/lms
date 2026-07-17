<?php

namespace App\Livewire\Schools;

use App\Models\SchoolTier;
use App\Services\PricingTierService;
use App\Services\SchoolService;
use App\Services\TierChangeService;
use Illuminate\Support\Collection as BaseCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SchoolEdit extends Component
{
    public string $schoolId;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(string $school, SchoolService $schoolService): void
    {
        $this->schoolId = $school;

        if (! $schoolService->find($school)) {
            abort(404, 'School not found');
        }
    }

    #[Computed]
    public function school()
    {
        return resolve(SchoolService::class)->findWith($this->schoolId, ['tier', 'schoolTiers']);
    }

    #[Computed]
    public function availableTiers(): BaseCollection
    {
        return resolve(PricingTierService::class)->get(['is_active' => true]);
    }

    #[Computed]
    public function currentSchoolTier(): ?SchoolTier
    {
        return $this->school->schoolTiers()
            ->latest('started_at')
            ->first();
    }

    #[Computed]
    public function tierFeatures(): BaseCollection
    {
        return $this->school->tier?->features()->get() ?? collect();
    }

    #[Computed]
    public function tierLimits(): BaseCollection
    {
        return $this->school->tier?->limits()->get() ?? collect();
    }

    public function confirmChange($tierId, SchoolService $schoolService, PricingTierService $pricingTierService, TierChangeService $tierChangeService): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $school = $schoolService->findWith($this->schoolId, ['tier']);
            $newTier = $pricingTierService->find($tierId);

            if (! $newTier) {
                $this->errorMessage = 'Selected tier not found.';

                return;
            }

            if ($tierId == $school->tier_id) {
                $this->errorMessage = 'The selected tier is the same as the current tier.';

                return;
            }

            // Use TierChangeService to handle tier change
            $tierChangeService->initiateTierChange($school, $newTier);

            $this->successMessage = "School tier changed to {$newTier->name} successfully.";

            // Reset computed properties
            $this->resetComputed();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to change tier: '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.schools.school-edit')
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
