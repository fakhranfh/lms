@extends('master')

@section('title', 'Tier Management')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')
<div class="flex h-screen flex-col">
    <x-topbar :title="'Tier Management'" :showBackButton="false" />

    <div class="flex flex-1 overflow-hidden">
        <x-sidebar />

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto py-space-lg px-gutter">
            <div class="space-y-space-lg">
                <div class="space-y-space-md">
                    <h1 class="font-headline-md text-headline-md text-on-surface">Tier Management</h1>
                    <p class="font-body-md text-body-md text-secondary">Manage your subscription tier and features</p>
                </div>

                @if ($isDemoMode)
                    <div class="p-space-lg bg-warning/10 border border-warning/20 rounded-lg flex items-center gap-space-md">
                        <span class="material-symbols-outlined text-warning text-[20px]" data-weight="fill">info</span>
                        <p class="font-body-md text-body-md text-warning">This is a demo account. Tier management is read-only.</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="p-space-lg bg-error/10 border border-error/20 rounded-lg space-y-space-sm">
                        <p class="font-label-md text-label-md text-error uppercase">Error</p>
                        <ul class="space-y-space-xs">
                            @foreach ($errors->all() as $error)
                                <li class="font-body-md text-body-md text-error">• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="p-space-lg bg-success/10 border border-success/20 rounded-lg">
                        <p class="font-body-md text-body-md text-success">{{ session('success') }}</p>
                    </div>
                @endif

                <!-- Pending Tier Change Banner -->
                @if ($pendingTier)
                    <div class="p-space-lg bg-info/10 border border-info/20 rounded-lg">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-label-md text-label-md text-info uppercase">Pending Tier Change</p>
                                <p class="font-body-md text-body-md text-info mt-space-sm">
                                    Upgrading to <strong>{{ $pendingTier->tier->name }}</strong>. Awaiting payment confirmation.
                                </p>
                            </div>
                            <form method="POST" action="{{ route('tier-management.cancel') }}" class="inline">
                                @csrf
                                <button type="submit" class="px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Current Tier Card -->
                <div class="p-space-lg bg-surface border border-outline-variant rounded-lg space-y-space-md">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Current Tier</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg">
                        <div>
                            <p class="font-label-md text-label-md text-secondary uppercase">Tier Name</p>
                            <p class="font-headline-md text-headline-md text-on-surface">{{ $currentTier->name }}</p>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-secondary uppercase">Price</p>
                            <p class="font-headline-md text-headline-md text-on-surface">
                                @if ($currentTier->price == 0)
                                    Free
                                @else
                                    Rp {{ number_format((int) $currentTier->price) }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-secondary uppercase">Billing Period</p>
                            <p class="font-headline-md text-headline-md text-on-surface">{{ $currentTier->billing_period->label() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Upgrade Tiers -->
                @if ($upgradeTiers->count() > 0)
                    <div class="space-y-space-md">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Available Upgrades</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
                            @foreach ($upgradeTiers as $tier)
                                <div class="p-space-lg bg-surface border border-outline-variant rounded-lg flex flex-col hover:border-outline transition-colors">
                                    <div class="space-y-space-md">
                                        <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $tier->name }}</h3>
                                        <p class="font-headline-lg text-headline-lg text-on-surface">
                                            @if ($tier->price == 0)
                                                Free
                                            @else
                                                Rp {{ number_format((int) $tier->price) }}<span class="font-body-md text-body-md text-secondary">/{{ $tier->billing_period->label() }}</span>
                                            @endif
                                        </p>

                                        @php
                                            $proration = $prorations[$tier->id] ?? 0;
                                        @endphp

                                        @if ($proration != 0)
                                            <div class="p-space-md bg-info/10 rounded-lg">
                                                <p class="font-label-md text-label-md text-info">
                                                    @if ($proration > 0)
                                                        Additional charge: Rp {{ number_format((int) $proration) }}
                                                    @else
                                                        Credit: Rp {{ number_format((int) abs($proration)) }}
                                                    @endif
                                                </p>
                                            </div>
                                        @endif

                                        @if ($tier->features->count() > 0)
                                            <div class="space-y-space-xs">
                                                <p class="font-label-md text-label-md text-on-surface uppercase">Features:</p>
                                                <ul class="font-body-sm text-body-sm text-secondary space-y-space-xs">
                                                    @foreach ($tier->features as $feature)
                                                        @php
                                                            $displayLabel = $feature->label ?? str($feature->feature_key)->replace('_', ' ')->title();
                                                            $displayLabel = preg_replace_callback('/\b(Sso|Api)\b/i', fn($m) => strtoupper($m[0]), $displayLabel);
                                                        @endphp
                                                        <li>✓ {{ $displayLabel }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($isDemoMode)
                                        <div class="mt-auto pt-space-md font-body-md text-body-md text-secondary text-center">Demo accounts cannot upgrade</div>
                                    @elseif ($enabledGateways->count() > 0)
                                        <form method="POST" action="{{ route('tier-management.change') }}" class="space-y-space-md mt-auto pt-space-md">
                                            @csrf
                                            <input type="hidden" name="tier_id" value="{{ $tier->id }}">

                                            @if ($enabledGateways->count() > 1)
                                                <select name="gateway_name" class="w-full px-space-md py-space-sm border border-outline-variant rounded-lg font-body-md text-body-md" required>
                                                    <option value="">Select payment method</option>
                                                    @foreach ($enabledGateways as $gateway)
                                                        <option value="{{ $gateway->paymentGatewayType->name }}">
                                                            {{ $gateway->paymentGatewayType->label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="hidden" name="gateway_name" value="{{ $enabledGateways->first()->paymentGatewayType->name }}">
                                            @endif

                                            <button type="submit" class="w-full px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                                                Upgrade Now
                                            </button>
                                        </form>
                                    @else
                                        <div class="mt-auto pt-space-md font-body-md text-body-md text-error">No payment gateway configured</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Downgrade Tiers -->
                @if ($downgradeTiers->count() > 0)
                    <div class="space-y-space-md">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Available Downgrades</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
                            @foreach ($downgradeTiers as $tier)
                                <div class="p-space-lg bg-surface border border-outline-variant rounded-lg flex flex-col hover:border-outline transition-colors">
                                    <div class="space-y-space-md">
                                        <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $tier->name }}</h3>
                                        <p class="font-headline-lg text-headline-lg text-on-surface">
                                            @if ($tier->price == 0)
                                                Free
                                            @else
                                                Rp {{ number_format((int) $tier->price) }}<span class="font-body-md text-body-md text-secondary">/{{ $tier->billing_period->label() }}</span>
                                            @endif
                                        </p>

                                        @php
                                            $proration = $prorations[$tier->id] ?? 0;
                                        @endphp

                                        @if ($proration != 0)
                                            <div class="p-space-md bg-success/10 rounded-lg">
                                                <p class="font-label-md text-label-md text-success">
                                                    @if ($proration < 0)
                                                        Refund: Rp {{ number_format((int) abs($proration)) }}
                                                    @else
                                                        Additional charge: Rp {{ number_format((int) $proration) }}
                                                    @endif
                                                </p>
                                            </div>
                                        @endif

                                        @if ($tier->features->count() > 0)
                                            <div class="space-y-space-xs">
                                                <p class="font-label-md text-label-md text-on-surface uppercase">Features:</p>
                                                <ul class="font-body-sm text-body-sm text-secondary space-y-space-xs">
                                                    @foreach ($tier->features as $feature)
                                                        @php
                                                            $displayLabel = $feature->label ?? str($feature->feature_key)->replace('_', ' ')->title();
                                                            $displayLabel = preg_replace_callback('/\b(Sso|Api)\b/i', fn($m) => strtoupper($m[0]), $displayLabel);
                                                        @endphp
                                                        <li>✓ {{ $displayLabel }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($isDemoMode)
                                        <div class="mt-auto pt-space-md font-body-md text-body-md text-secondary text-center">Demo accounts cannot downgrade</div>
                                    @elseif ($enabledGateways->count() > 0)
                                        <form method="POST" action="{{ route('tier-management.change') }}" class="mt-auto pt-space-md">
                                            @csrf
                                            <input type="hidden" name="tier_id" value="{{ $tier->id }}">
                                            <button type="submit" class="w-full px-space-lg py-space-sm bg-secondary text-on-secondary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                                                Downgrade
                                            </button>
                                        </form>
                                    @else
                                        <div class="mt-auto pt-space-md font-body-md text-body-md text-error">No payment gateway configured</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>
@endsection
