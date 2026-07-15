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

    public ?int $newTierId = null;

    public bool $showChangeConfirmation = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(string $school, SchoolService $schoolService): void
    {
        $this->schoolId = $school;

        abort_unless(auth()->user()->can('manage_schools') || auth()->user()->hasRole('admin'), 403);

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

    public function initiateChange(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->newTierId) {
            $this->errorMessage = 'Please select a new tier.';

            return;
        }

        if ($this->newTierId == $this->school->tier_id) {
            $this->errorMessage = 'The selected tier is the same as the current tier.';

            return;
        }

        $this->showChangeConfirmation = true;
    }

    public function confirmChange(SchoolService $schoolService, PricingTierService $pricingTierService, TierChangeService $tierChangeService): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $school = $schoolService->findWith($this->schoolId, ['tier']);
            $newTier = $pricingTierService->find($this->newTierId);

            if (! $newTier) {
                $this->errorMessage = 'Selected tier not found.';

                return;
            }

            // Use TierChangeService to handle tier change
            $tierChangeService->initiateTierChange($school, $newTier);

            $this->showChangeConfirmation = false;
            $this->newTierId = null;
            $this->successMessage = "School tier changed to {$newTier->name} successfully.";

            // Reset computed properties
            $this->resetComputed();
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to change tier: '.$e->getMessage();
        }
    }

    public function cancelChange(): void
    {
        $this->showChangeConfirmation = false;
        $this->newTierId = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('livewire.schools.school-edit')
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
