<?php

namespace App\Livewire\PricingTiers;

use App\Enums\BillingPeriod;
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

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return (new StorePricingTierRequest)->rules();
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

    public function store(PricingTierService $tierService)
    {
        abort_unless(auth()->user()->can('pricing-tiers.create'), 403);

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

        $tierService->create([
            ...$data,
            'limits' => array_values($limitsToStore),
        ]);

        session()->flash('success', 'Pricing tier created successfully.');

        return redirect()->route('admin.pricing-tiers.index');
    }

    public function render()
    {
        return view('livewire.pricing-tiers.pricing-tier-create', [
            'billingPeriods' => BillingPeriod::cases(),
        ])
            ->extends('layouts.admin')
            ->section('admin-content');
    }
}
