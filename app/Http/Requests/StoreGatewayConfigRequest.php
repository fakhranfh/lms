<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGatewayConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
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
