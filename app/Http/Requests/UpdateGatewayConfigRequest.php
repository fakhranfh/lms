<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGatewayConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(RoleName::Admin);
    }

    public function rules(): array
    {
        return [
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
}
