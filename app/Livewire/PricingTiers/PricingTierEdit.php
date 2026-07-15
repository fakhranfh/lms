<?php

namespace App\Livewire\PricingTiers;

use App\Enums\BillingPeriod;
use App\Enums\TierFeature;
use App\Enums\TierLimit;
use App\Http\Requests\PricingTier\UpdatePricingTierRequest;
use App\Models\PricingTier;
use App\Services\PricingTierService;
use Livewire\Component;

class PricingTierEdit extends Component
{
    public PricingTier $tier;

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
        $this->tier->load(['features', 'limits']);

        $this->name = $this->tier->name;
        $this->slug = $this->tier->slug;
        $this->description = $this->tier->description;
        $this->price = (string) $this->tier->price;
        $this->currency = $this->tier->currency;
        $this->billing_period = $this->tier->billing_period->value;
        $this->is_active = $this->tier->is_active;

        $tierFeaturesMap = $this->tier->features->keyBy('feature_key')->toArray();

        $this->features = collect(TierFeature::cases())
            ->map(fn ($feature) => [
                'feature_key' => $feature->value,
                'is_enabled' => $tierFeaturesMap[$feature->value]['is_enabled'] ?? false,
            ])
            ->toArray();

        $tierLimitsMap = $this->tier->limits->keyBy('limit_key')->toArray();

        $this->limits = collect(TierLimit::cases())
            ->map(fn ($limit) => [
                'limit_key' => $limit->value,
                'limit_value' => isset($tierLimitsMap[$limit->value]) && $tierLimitsMap[$limit->value]['limit_value']
                    ? (string) $tierLimitsMap[$limit->value]['limit_value']
                    : '',
            ])
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return (new UpdatePricingTierRequest)->rules();
    }

    public function update(PricingTierService $tierService)
    {
        abort_unless(auth()->user()->can('pricing-tiers.update'), 403);

        $data = $this->validate();

        $featuresToStore = array_filter($data['features'] ?? [], fn ($feature) => $feature['is_enabled']);
        $limitsToStore = array_filter(
            array_map(fn ($limit) => [
                'limit_key' => $limit['limit_key'],
                'limit_value' => empty($limit['limit_value']) ? null : (int) $limit['limit_value'],
            ], $data['limits'] ?? []),
            fn ($limit) => ! empty($limit['limit_key'])
        );

        $tierService->update($this->tier->id, [
            ...$data,
            'features' => array_values($featuresToStore),
            'limits' => array_values($limitsToStore),
        ]);

        session()->flash('success', 'Pricing tier updated successfully.');

        return redirect()->route('admin.pricing-tiers.index');
    }

    public function render()
    {
        return view('livewire.pricing-tiers.pricing-tier-edit', [
            'billingPeriods' => BillingPeriod::cases(),
            'availableFeatures' => TierFeature::cases(),
            'availableLimits' => TierLimit::cases(),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
