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
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('pricing_tiers', 'name')->ignore($this->route('tier'))],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:3'],
            'billing_period' => ['required', Rule::enum(BillingPeriod::class)],
            'is_active' => ['boolean'],
            'features' => ['array'],
            'features.*.feature_key' => ['required', 'string', 'max:255'],
            'features.*.is_enabled' => ['boolean'],
            'limits' => ['array'],
            'limits.*.limit_key' => ['required', 'string', 'max:255'],
            'limits.*.limit_value' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
