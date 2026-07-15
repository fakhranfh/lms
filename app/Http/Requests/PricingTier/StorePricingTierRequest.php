<?php

namespace App\Http\Requests\PricingTier;

use App\Enums\BillingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePricingTierRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:pricing_tiers,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:pricing_tiers,slug'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) {
                if (! is_int($value) && (int) $value != $value) {
                    $fail('The price must be a whole number.');
                }
            }],
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
