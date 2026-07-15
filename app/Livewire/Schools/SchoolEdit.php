<?php

namespace App\Livewire\Schools;

use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolTier;
use App\Models\TierChange;
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

    public function mount(string $school): void
    {
        $this->schoolId = $school;

        abort_unless(auth()->user()->can('manage_schools') || auth()->user()->hasRole('admin'), 403);

        if (! School::find($school)) {
            abort(404, 'School not found');
        }
    }

    #[Computed]
    public function school(): School
    {
        return School::with(['tier', 'schoolTiers'])->findOrFail($this->schoolId);
    }

    #[Computed]
    public function availableTiers(): BaseCollection
    {
        return PricingTier::where('is_active', true)->get();
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

    public function confirmChange(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $oldTierId = $this->school->tier_id;
            $newTier = PricingTier::findOrFail($this->newTierId);

            // Update school tier
            $this->school->update(['tier_id' => $this->newTierId]);

            // Create SchoolTier record
            $schoolTier = SchoolTier::create([
                'school_id' => $this->school->id,
                'tier_id' => $this->newTierId,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => null,
                'renewal_date' => null,
                'auto_renew' => true,
                'payment_method' => null,
            ]);

            // Create tier change record
            TierChange::create([
                'school_tier_id' => $schoolTier->id,
                'from_tier_id' => $oldTierId,
                'to_tier_id' => $this->newTierId,
                'change_type' => $this->newTierId > $oldTierId ? 'upgrade' : 'downgrade',
                'changed_at' => now(),
            ]);

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
