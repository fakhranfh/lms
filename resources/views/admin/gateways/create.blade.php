@extends('layouts.admin')

@section('title', 'Add Payment Gateway')

@section('admin-content')
    <div class="max-w-2xl">
        <h1 class="font-headline-md text-headline-md text-on-surface mb-8">Add Payment Gateway</h1>

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

        <form action="{{ route('admin.gateways.store') }}" method="POST" class="bg-surface border border-outline rounded-lg p-8">
            @csrf

            <div class="mb-8">
                <label for="gateway_type_id" class="block text-body-lg font-medium text-on-surface mb-2">
                    Payment Gateway <span class="text-error">*</span>
                </label>
                <select name="gateway_type_id" id="gateway_type_id" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface @error('gateway_type_id') border-error @enderror" required onchange="updateCredentialFields()">
                    <option value="">-- Select Gateway --</option>
                    @foreach ($gateways as $gateway)
                        <option value="{{ $gateway->id }}" data-name="{{ $gateway->name }}">{{ $gateway->label }}</option>
                    @endforeach
                </select>
                @error('gateway_type_id')
                    <p class="text-error text-body-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="credentials-section" class="mb-8 hidden">
                <h2 class="text-body-lg font-medium text-on-surface mb-4">Credentials</h2>

                <div id="midtrans-credentials" class="hidden space-y-6">
                    <div>
                        <label for="credentials[server_key]" class="block text-body-md font-medium text-on-surface mb-2">
                            Server Key <span class="text-error">*</span>
                        </label>
                        <input type="password" name="credentials[server_key]" id="credentials[server_key]" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Enter Midtrans server key">
                        @error('credentials.server_key')
                            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="credentials[client_key]" class="block text-body-md font-medium text-on-surface mb-2">
                            Client Key <span class="text-error">*</span>
                        </label>
                        <input type="text" name="credentials[client_key]" id="credentials[client_key]" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Enter Midtrans client key">
                        @error('credentials.client_key')
                            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div id="xendit-credentials" class="hidden space-y-6">
                    <div>
                        <label for="credentials[api_key]" class="block text-body-md font-medium text-on-surface mb-2">
                            API Key <span class="text-error">*</span>
                        </label>
                        <input type="password" name="credentials[api_key]" id="credentials[api_key]" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Enter Xendit API key">
                        @error('credentials.api_key')
                            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="credentials[callback_token]" class="block text-body-md font-medium text-on-surface mb-2">
                            Callback Token
                        </label>
                        <input type="password" name="credentials[callback_token]" id="credentials[callback_token]" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Enter Xendit callback token (optional)">
                        @error('credentials.callback_token')
                            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <span class="block text-body-md font-medium text-on-surface mb-2">
                            Enabled Payment Channels <span class="text-error">*</span>
                        </span>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (\App\Enums\XenditChannel::cases() as $channel)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="enabled_channels[]" value="{{ $channel->value }}" class="rounded">
                                    <span class="text-body-sm text-on-surface">{{ $channel->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('enabled_channels')
                            <p class="text-error text-body-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-8">
                <div>
                    <label for="is_sandbox_mode" class="flex items-center gap-3">
                        <input type="checkbox" name="is_sandbox_mode" id="is_sandbox_mode" value="1" class="rounded" checked>
                        <span class="text-body-md font-medium text-on-surface">Sandbox Mode</span>
                    </label>
                    <p class="text-body-sm text-on-surface-variant mt-2">Use test/sandbox environment</p>
                </div>

                <div>
                    <label for="is_enabled" class="flex items-center gap-3">
                        <input type="checkbox" name="is_enabled" id="is_enabled" value="1" class="rounded">
                        <span class="text-body-md font-medium text-on-surface">Enable Gateway</span>
                    </label>
                    <p class="text-body-sm text-on-surface-variant mt-2">Activate this gateway for payments</p>
                </div>
            </div>

            <div class="mb-8">
                <label for="webhook_secret" class="block text-body-md font-medium text-on-surface mb-2">
                    Webhook Secret
                </label>
                <input type="password" name="webhook_secret" id="webhook_secret" class="w-full px-4 py-2 border border-outline rounded-lg text-on-surface" placeholder="Optional webhook secret for signature verification">
                @error('webhook_secret')
                    <p class="text-error text-body-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-3 bg-primary text-on-primary rounded-lg font-medium hover:opacity-90 transition">
                    Add Gateway
                </button>
                <a href="{{ route('admin.gateways.index') }}" class="px-6 py-3 border border-outline text-on-surface rounded-lg font-medium hover:bg-surface-container transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        function updateCredentialFields() {
            const gatewaySelect = document.getElementById('gateway_type_id');
            const selectedOption = gatewaySelect.options[gatewaySelect.selectedIndex];
            const gatewayName = selectedOption.dataset.name;

            const credentialsSection = document.getElementById('credentials-section');
            const midtransSection = document.getElementById('midtrans-credentials');
            const xenditSection = document.getElementById('xendit-credentials');

            if (!gatewayName) {
                credentialsSection.classList.add('hidden');
                midtransSection.classList.add('hidden');
                xenditSection.classList.add('hidden');
                return;
            }

            credentialsSection.classList.remove('hidden');

            if (gatewayName === 'midtrans') {
                midtransSection.classList.remove('hidden');
                xenditSection.classList.add('hidden');
            } else if (gatewayName === 'xendit') {
                xenditSection.classList.remove('hidden');
                midtransSection.classList.add('hidden');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', updateCredentialFields);
    </script>
@endsection
