<?php

namespace App\Livewire\PricingTiers;

use App\Services\PricingTierService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PricingTierIndex extends Component
{
    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->successMessage = session('success');
    }

    public function destroy(int $id, PricingTierService $tierService): void
    {
        abort_unless(auth()->user()->can('pricing-tiers.delete'), 403);

        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $tierService->delete($id);
            $this->successMessage = __('Pricing tier deleted successfully.');
        } catch (ValidationException $exception) {
            $this->errorMessage = collect($exception->errors())->flatten()->first();
        }
    }

    public function render(PricingTierService $tierService)
    {
        return view('livewire.pricing-tiers.pricing-tier-index', [
            'tiers' => $tierService->get([], ['features', 'limits']),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
