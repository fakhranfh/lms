@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 px-4">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Tier Management</h1>
        <p class="text-gray-600 mt-2">Manage your subscription tier and features</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <!-- Pending Tier Change Banner -->
    @if ($pendingTier)
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-blue-900 font-semibold">Pending Tier Change</p>
                    <p class="text-blue-700 text-sm mt-1">
                        Upgrading to <strong>{{ $pendingTier->tier->name }}</strong>. Awaiting payment confirmation.
                    </p>
                </div>
                <form method="POST" action="{{ route('tier-management.cancel') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 rounded">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Current Tier Card -->
    <div class="mb-8 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Current Tier</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm font-semibold text-gray-600">Tier Name</p>
                <p class="text-2xl font-bold text-gray-900">{{ $currentTier->name }}</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-600">Price</p>
                <p class="text-2xl font-bold text-gray-900">
                    @if ($currentTier->price == 0)
                        Free
                    @else
                        Rp {{ number_format((int) $currentTier->price) }}
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-600">Billing Period</p>
                <p class="text-2xl font-bold text-gray-900">{{ ucfirst($currentTier->billing_period->value) }}</p>
            </div>
        </div>
    </div>

    <!-- Upgrade Tiers -->
    @if ($upgradeTiers->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Available Upgrades</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($upgradeTiers as $tier)
                    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow hover:shadow-lg transition">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $tier->name }}</h3>
                        <p class="text-3xl font-bold text-gray-900 mb-4">
                            Rp {{ number_format((int) $tier->price) }}<span class="text-sm text-gray-600">/{{ $tier->billing_period->value }}</span>
                        </p>

                        @php
                            $proration = $prorations[$tier->id] ?? 0;
                        @endphp

                        @if ($proration != 0)
                            <div class="mb-4 p-3 bg-blue-50 rounded">
                                <p class="text-sm font-semibold text-blue-900">
                                    @if ($proration > 0)
                                        Additional charge: Rp {{ number_format((int) $proration) }}
                                    @else
                                        Credit: Rp {{ number_format((int) abs($proration)) }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if ($tier->features->count() > 0)
                            <div class="mb-4">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Features:</p>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    @foreach ($tier->features as $feature)
                                        <li>✓ {{ $feature->feature_key }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tier-management.change') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="tier_id" value="{{ $tier->id }}">

                            @if ($enabledGateways->count() > 1)
                                <select name="gateway_name" class="w-full px-3 py-2 border border-gray-300 rounded text-sm" required>
                                    <option value="">Select payment method</option>
                                    @foreach ($enabledGateways as $gateway)
                                        <option value="{{ $gateway->paymentGatewayType->name }}">
                                            {{ $gateway->paymentGatewayType->label }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif ($enabledGateways->count() == 1)
                                <input type="hidden" name="gateway_name" value="{{ $enabledGateways->first()->paymentGatewayType->name }}">
                            @else
                                <div class="text-sm text-red-600 font-semibold">No payment gateway configured</div>
                            @endif

                            @if ($enabledGateways->count() > 0)
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded hover:bg-blue-700 transition">
                                    Upgrade Now
                                </button>
                            @endif
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Downgrade Tiers -->
    @if ($downgradeTiers->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Available Downgrades</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($downgradeTiers as $tier)
                    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow hover:shadow-lg transition">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $tier->name }}</h3>
                        <p class="text-3xl font-bold text-gray-900 mb-4">
                            @if ($tier->price == 0)
                                Free
                            @else
                                Rp {{ number_format((int) $tier->price) }}<span class="text-sm text-gray-600">/{{ $tier->billing_period->value }}</span>
                            @endif
                        </p>

                        @php
                            $proration = $prorations[$tier->id] ?? 0;
                        @endphp

                        @if ($proration != 0)
                            <div class="mb-4 p-3 bg-green-50 rounded">
                                <p class="text-sm font-semibold text-green-900">
                                    @if ($proration < 0)
                                        Refund: Rp {{ number_format((int) abs($proration)) }}
                                    @else
                                        Additional charge: Rp {{ number_format((int) $proration) }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if ($tier->features->count() > 0)
                            <div class="mb-4">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Features:</p>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    @foreach ($tier->features as $feature)
                                        <li>✓ {{ $feature->feature_key }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tier-management.change') }}">
                            @csrf
                            <input type="hidden" name="tier_id" value="{{ $tier->id }}">
                            <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white font-medium rounded hover:bg-gray-700 transition">
                                Downgrade
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
