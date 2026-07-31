<?php

namespace App\Http\Requests\PricingTier;

use App\Enums\BillingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePricingTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tierId = $this->route('tier')->id ?? $this->input('tier_id');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('pricing_tiers', 'name')->ignore($tierId)],
            'description' => ['required', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'billing_period' => ['required', Rule::enum(BillingPeriod::class)],
            'is_active' => ['boolean'],
            'features' => ['array'],
            'features.*.feature_key' => ['required', 'string'],
            'features.*.is_enabled' => ['boolean'],
            'limits' => ['array'],
            'limits.*.limit_key' => ['required', 'string'],
            'limits.*.limit_value' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
