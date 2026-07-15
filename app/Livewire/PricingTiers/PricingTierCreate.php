<?php

namespace App\Livewire\PricingTiers;

use App\Enums\BillingPeriod;
use App\Enums\TierFeature;
use App\Enums\TierLimit;
use App\Http\Requests\PricingTier\StorePricingTierRequest;
use App\Services\PricingTierService;
use Livewire\Component;

class PricingTierCreate extends Component
{
    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $price = '0';

    public string $currency = 'IDR';

    public string $billing_period = 'Monthly';

    public bool $is_active = true;

    /**
     * @var array<int, array{feature_key: string, is_enabled: bool}>
     */
    public array $features = [];

    /**
     * @var array<int, array{limit_key: string, limit_value: string}>
     */
    public array $limits = [];

    public function mount(): void
    {
        $this->features = collect(TierFeature::cases())
            ->map(fn ($feature) => [
                'feature_key' => $feature->value,
                'is_enabled' => false,
            ])
            ->toArray();

        $this->limits = collect(TierLimit::cases())
            ->map(fn ($limit) => [
                'limit_key' => $limit->value,
                'limit_value' => '',
            ])
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return (new StorePricingTierRequest)->rules();
    }

    public function store(PricingTierService $tierService)
    {
        abort_unless(auth()->user()->can('pricing-tiers.create'), 403);

        $data = $this->validate();

        $featuresToStore = array_filter($data['features'] ?? [], fn ($feature) => $feature['is_enabled']);
        $limitsToStore = array_filter(
            array_map(fn ($limit) => [
                'limit_key' => $limit['limit_key'],
                'limit_value' => empty($limit['limit_value']) ? null : (int) $limit['limit_value'],
            ], $data['limits'] ?? []),
            fn ($limit) => ! empty($limit['limit_key'])
        );

        $tierService->create([
            ...$data,
            'features' => array_values($featuresToStore),
            'limits' => array_values($limitsToStore),
        ]);

        session()->flash('success', 'Pricing tier created successfully.');

        return redirect()->route('admin.pricing-tiers.index');
    }

    public function render()
    {
        return view('livewire.pricing-tiers.pricing-tier-create', [
            'billingPeriods' => BillingPeriod::cases(),
            'availableFeatures' => TierFeature::cases(),
            'availableLimits' => TierLimit::cases(),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
