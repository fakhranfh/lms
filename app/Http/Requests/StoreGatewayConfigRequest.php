<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use App\Enums\XenditChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGatewayConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(RoleName::Admin);
    }

    public function rules(): array
    {
        return [
            'gateway_type_id' => ['required', 'exists:payment_gateway_types,id'],
            'is_enabled' => ['boolean'],
            'is_sandbox_mode' => ['boolean'],
            'webhook_secret' => ['nullable', 'string'],
            'credentials' => ['required', 'array'],
            'credentials.server_key' => ['nullable', 'string'],
            'credentials.client_key' => ['nullable', 'string'],
            'credentials.api_key' => ['nullable', 'string'],
            'credentials.callback_token' => ['nullable', 'string'],
            'enabled_channels' => ['nullable', 'array'],
            'enabled_channels.*' => [Rule::enum(XenditChannel::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'gateway_type_id.required' => 'Please select a payment gateway.',
            'gateway_type_id.exists' => 'The selected gateway type is invalid.',
        ];
    }
}
