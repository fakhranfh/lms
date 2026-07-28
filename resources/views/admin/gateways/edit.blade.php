@extends('layouts.admin')

@section('title', 'Edit Payment Gateway')

@section('admin-content')
    <div class="max-w-2xl">
        <h1 class="font-headline-md text-headline-md text-on-surface mb-8">Edit Payment Gateway</h1>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-error-container border border-error rounded-lg">
                <p class="font-medium text-error mb-2">Please fix the following errors:</p>
                <ul class="list-disc list-inside text-error">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.gateways.update', $gateway) }}" method="POST" class="bg-surface border border-outline rounded-lg p-8" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            @method('PUT')

            <div class="mb-8">
                <label for="gateway_type_id" class="block text-body-lg font-medium text-on-surface mb-2">
                    Payment Gateway
                </label>
                <div class="px-4 py-2 border border-outline rounded-lg text-on-surface bg-surface-container">
                    {{ $gateway->paymentGatewayType->label }}
                </div>
                <p class="text-body-sm text-on-surface-variant mt-2">Gateway type cannot be changed after creation</p>
            </div>

            <div id="credentials-section" class="mb-8">
                <h2 class="text-body-lg font-medium text-on-surface mb-4">Credentials</h2>

                @if ($gateway->paymentGatewayType->name === 'midtrans')
                    <div class="space-y-6">
                        <div>
                            <label for="credentials[server_key]" class="block text-body-md font-medium text-on-surface mb-2">
                                Server Key
                            </label>
                            <input type="password" name="credentials[server_key]" id="credentials[server_key]" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Enter Midtrans server key" value="">
                            <p class="text-body-sm text-on-surface-variant mt-1">Leave blank to keep current value</p>
                        </div>

                        <div>
                            <label for="credentials[client_key]" class="block text-body-md font-medium text-on-surface mb-2">
                                Client Key
                            </label>
                            <input type="password" name="credentials[client_key]" id="credentials[client_key]" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Enter Midtrans client key" value="">
                            <p class="text-body-sm text-on-surface-variant mt-1">Leave blank to keep current value</p>
                        </div>
                    </div>
                @elseif ($gateway->paymentGatewayType->name === 'xendit')
                    <div class="space-y-6">
                        <div>
                            <label for="credentials[api_key]" class="block text-body-md font-medium text-on-surface mb-2">
                                API Key
                            </label>
                            <input type="password" name="credentials[api_key]" id="credentials[api_key]" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Enter Xendit API key" value="">
                            <p class="text-body-sm text-on-surface-variant mt-1">Leave blank to keep current value</p>
                        </div>

                        <div>
                            <label for="credentials[callback_token]" class="block text-body-md font-medium text-on-surface mb-2">
                                Callback Token
                            </label>
                            <input type="password" name="credentials[callback_token]" id="credentials[callback_token]" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Enter Xendit callback token" value="">
                            <p class="text-body-sm text-on-surface-variant mt-1">Leave blank to keep current value</p>
                        </div>

                        <div>
                            <span class="block text-body-md font-medium text-on-surface mb-2">
                                Enabled Payment Channels <span class="text-error">*</span>
                            </span>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach (\App\Enums\XenditChannel::cases() as $channel)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="enabled_channels[]" value="{{ $channel->value }}" class="rounded" {{ in_array($channel->value, $gateway->enabled_channels ?? []) ? 'checked' : '' }}>
                                        <span class="text-body-sm text-on-surface">{{ $channel->label() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('enabled_channels')
                                <p class="text-error text-body-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-6 mb-8">
                <div>
                    <label for="is_sandbox_mode" class="flex items-center gap-3">
                        <input type="checkbox" name="is_sandbox_mode" id="is_sandbox_mode" value="1" class="rounded" {{ $gateway->is_sandbox_mode ? 'checked' : '' }}>
                        <span class="text-body-md font-medium text-on-surface">Sandbox Mode</span>
                    </label>
                    <p class="text-body-sm text-on-surface-variant mt-2">Use test/sandbox environment</p>
                </div>

                <div>
                    <label for="is_enabled" class="flex items-center gap-3">
                        <input type="checkbox" name="is_enabled" id="is_enabled" value="1" class="rounded" {{ $gateway->is_enabled ? 'checked' : '' }}>
                        <span class="text-body-md font-medium text-on-surface">Enable Gateway</span>
                    </label>
                    <p class="text-body-sm text-on-surface-variant mt-2">Activate this gateway for payments</p>
                </div>
            </div>

            <div class="mb-8">
                <label for="webhook_secret" class="block text-body-md font-medium text-on-surface mb-2">
                    Webhook Secret
                </label>
                <input type="password" name="webhook_secret" id="webhook_secret" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Optional webhook secret for signature verification" value="">
                <p class="text-body-sm text-on-surface-variant mt-2">Leave blank to keep current value</p>
            </div>

            <div class="flex gap-4">
                <button type="submit" :disabled="loading" class="px-6 py-3 bg-primary text-on-primary rounded-lg font-medium hover:opacity-90 transition disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="loading" x-cloak class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Updating...' : 'Update Gateway'"></span>
                </button>
                <a href="{{ route('admin.gateways.index') }}" class="px-6 py-3 border border-outline text-on-surface rounded-lg font-medium hover:bg-surface-container transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
