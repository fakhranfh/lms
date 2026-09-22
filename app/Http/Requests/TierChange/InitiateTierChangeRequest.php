<?php

namespace App\Http\Requests\TierChange;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateTierChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tier_id' => [
                'required',
                'integer',
                Rule::exists('pricing_tiers', 'id')->where('is_active', true),
            ],
        ];
    }
}
