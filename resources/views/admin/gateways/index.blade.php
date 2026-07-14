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
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.gateways.edit', $gateway) }}" class="px-4 py-2 text-primary text-body-sm font-medium hover:bg-surface-container rounded transition">
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.gateways.test-connection', $gateway) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 text-info text-body-sm font-medium hover:bg-surface-container rounded transition">
                                                Test
                                            </button>
                                        </form>
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
