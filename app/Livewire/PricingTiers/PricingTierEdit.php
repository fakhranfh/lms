<?php

namespace App\Livewire\PricingTiers;

use App\Enums\BillingPeriod;
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

        $this->features = $this->tier->features->map(fn ($feature) => [
            'feature_key' => $feature->feature_key,
            'is_enabled' => $feature->is_enabled,
        ])->toArray();

        $this->limits = $this->tier->limits->map(fn ($limit) => [
            'limit_key' => $limit->limit_key,
            'limit_value' => $limit->limit_value ? (string) $limit->limit_value : '',
        ])->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return (new UpdatePricingTierRequest)->rules();
    }

    public function addFeature(): void
    {
        $this->features[] = ['feature_key' => '', 'is_enabled' => true];
    }

    public function removeFeature(int $index): void
    {
        unset($this->features[$index]);
        $this->features = array_values($this->features);
    }

    public function addLimit(): void
    {
        $this->limits[] = ['limit_key' => '', 'limit_value' => ''];
    }

    public function removeLimit(int $index): void
    {
        unset($this->limits[$index]);
        $this->limits = array_values($this->limits);
    }

    public function update(PricingTierService $tierService)
    {
        abort_unless(auth()->user()->can('pricing-tiers.update'), 403);

        $data = $this->validate();

        $limitsToStore = array_filter(array_map(function ($limit) {
            if (empty($limit['limit_key'])) {
                return null;
            }

            return [
                'limit_key' => $limit['limit_key'],
                'limit_value' => empty($limit['limit_value']) ? null : (int) $limit['limit_value'],
            ];
        }, $data['limits'] ?? []));

        $tierService->update($this->tier->id, [
            ...$data,
            'limits' => array_values($limitsToStore),
        ]);

        session()->flash('success', 'Pricing tier updated successfully.');

        return redirect()->route('admin.pricing-tiers.index');
    }

    public function render()
    {
        return view('livewire.pricing-tiers.pricing-tier-edit', [
            'billingPeriods' => BillingPeriod::cases(),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
