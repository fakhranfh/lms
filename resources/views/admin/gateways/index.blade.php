@extends('layouts.admin')

@section('title', 'Payment Gateways')

@section('admin-content')
    <div class="p-gutter">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="font-headline-md text-headline-md text-on-surface">Payment Gateways</h1>
                <p class="text-body-md text-on-surface-variant mt-1">Manage payment gateway configurations</p>
            </div>
            <a href="{{ route('admin.gateways.create') }}" class="px-6 py-3 bg-primary text-on-primary rounded-lg font-medium hover:opacity-90 transition">
                Add Gateway
            </a>
        </div>

        @if ($message = Session::get('success'))
            <div class="mb-6 p-4 bg-success-container border border-success rounded-lg text-on-success-container">
                {{ $message }}
            </div>
        @endif

        @if ($message = Session::get('error'))
            <div class="mb-6 p-4 bg-error-container border border-error rounded-lg text-error">
                {{ $message }}
            </div>
        @endif

        @if ($message = Session::get('warning'))
            <div class="mb-6 p-4 bg-yellow-100 border border-yellow-300 rounded-lg text-yellow-800">
                {{ $message }}
            </div>
        @endif

        @if ($gateways->isEmpty())
            <div class="bg-surface border border-outline rounded-lg p-8 text-center">
                <p class="text-on-surface-variant mb-4">No payment gateways configured yet.</p>
                <a href="{{ route('admin.gateways.create') }}" class="text-primary font-medium hover:underline">
                    Configure your first gateway
                </a>
            </div>
        @else
            <div class="bg-surface border border-outline rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-surface-container border-b border-outline">
                        <tr>
                            <th class="px-6 py-4 text-left text-body-md font-medium text-on-surface">Gateway</th>
                            <th class="px-6 py-4 text-left text-body-md font-medium text-on-surface">Status</th>
                            <th class="px-6 py-4 text-left text-body-md font-medium text-on-surface">Mode</th>
                            <th class="px-6 py-4 text-left text-body-md font-medium text-on-surface">Credentials</th>
                            <th class="px-6 py-4 text-right text-body-md font-medium text-on-surface">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline">
                        @foreach ($gateways as $gateway)
                            <tr class="hover:bg-surface-container transition">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-on-surface">{{ $gateway->paymentGatewayType->label }}</span>
                                    <p class="text-body-sm text-on-surface-variant mt-1">{{ $gateway->paymentGatewayType->description }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($gateway->is_enabled)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-body-sm font-medium bg-success-container text-on-success-container">
                                            Enabled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-body-sm font-medium bg-surface-container text-on-surface-variant">
                                            Disabled
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($gateway->is_sandbox_mode)
                                        <span class="text-body-sm text-orange-600 font-medium">Sandbox</span>
                                    @else
                                        <span class="text-body-sm text-success font-medium">Production</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-body-sm text-on-surface-variant">
                                        {{ $gateway->credentials->count() }} configured
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2" @if (!empty($gateway->enabled_channels)) x-data="{ testModalOpen: false, channel: null, loading: false }" @endif>
                                        <a href="{{ route('admin.gateways.edit', $gateway) }}" class="px-4 py-2 text-primary text-body-sm font-medium hover:bg-surface-container rounded transition">
                                            Edit
                                        </a>

                                        @if (!empty($gateway->enabled_channels))
                                            <button type="button" @click="testModalOpen = true" class="px-4 py-2 text-info text-body-sm font-medium hover:bg-surface-container rounded transition">
                                                Test
                                            </button>

                                            <div x-show="testModalOpen" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                                                <div class="bg-surface rounded-lg shadow-xl max-w-sm w-full mx-4" @click.outside="testModalOpen = false">
                                                    <form action="{{ route('admin.gateways.test-connection', $gateway) }}" method="POST" class="p-6" @submit="loading = true">
                                                        @csrf
                                                        <h3 class="text-body-lg font-medium text-on-surface mb-4">Choose Payment Method</h3>
                                                        <div class="space-y-2 mb-6">
                                                            @foreach ($gateway->enabled_channels as $channelValue)
                                                                @php $channelEnum = \App\Enums\XenditChannel::from($channelValue); @endphp
                                                                <label class="flex items-center gap-3 px-3 py-2 border border-outline rounded-lg cursor-pointer hover:bg-surface-container">
                                                                    <input type="radio" name="channel" value="{{ $channelEnum->value }}" x-model="channel" required>
                                                                    <span class="text-body-sm text-on-surface">{{ $channelEnum->label() }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                        <div class="flex gap-3">
                                                            <button type="button" @click="testModalOpen = false" :disabled="loading" class="flex-1 px-4 py-2 border border-outline text-on-surface rounded-lg font-medium hover:bg-surface-container transition disabled:opacity-50">
                                                                Cancel
                                                            </button>
                                                            <button type="submit" :disabled="loading" class="flex-1 px-4 py-2 bg-primary text-on-primary rounded-lg font-medium hover:opacity-90 transition disabled:opacity-50 flex items-center justify-center gap-2">
                                                                <svg x-show="loading" x-cloak class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                                </svg>
                                                                <span x-text="loading ? 'Testing...' : 'Test'"></span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <form action="{{ route('admin.gateways.test-connection', $gateway) }}" method="POST" class="inline" x-data="{ loading: false }" @submit="loading = true">
                                                @csrf
                                                <button type="submit" :disabled="loading" class="px-4 py-2 text-info text-body-sm font-medium hover:bg-surface-container rounded transition disabled:opacity-50 inline-flex items-center gap-2">
                                                    <svg x-show="loading" x-cloak class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    <span x-text="loading ? 'Testing...' : 'Test'"></span>
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('admin.gateways.destroy', $gateway) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-4 py-2 text-error text-body-sm font-medium hover:bg-surface-container rounded transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
