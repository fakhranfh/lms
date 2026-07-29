@section('title', 'My Schools')

<div class="space-y-space-lg">
    <div class="flex items-center justify-between">
        <h1 class="font-headline-sm text-headline-sm text-on-surface">My Schools</h1>
        <a href="{{ route('get-started.school'.\App\Support\RootDomains::currentSuffix()) }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            Add School
        </a>
    </div>

    @if (session('success'))
        <div class="p-space-lg bg-success/10 border border-success/20 rounded-lg">
            <p class="font-body-md text-body-md text-success">{{ session('success') }}</p>
        </div>
    @endif

    @error('tier')
        <div class="p-space-lg bg-error/10 border border-error/20 rounded-lg">
            <p class="font-body-md text-body-md text-error">{{ $message }}</p>
        </div>
    @enderror

    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
        @if ($schools->isEmpty())
            <p class="font-body-md text-body-md text-secondary text-center py-space-lg">You don't manage any schools yet.</p>
        @else
            <ul class="divide-y divide-outline-variant">
                @foreach ($schools as $school)
                    @php
                        $port = request()->getPort();
                        $schoolUrl = $port && ! in_array($port, [80, 443])
                            ? request()->getScheme().'://'.$school->domain.':'.$port
                            : request()->getScheme().'://'.$school->domain;
                        $tierOverview = $tierOverviews[$school->id];
                    @endphp
                    <li x-data="{ open: false }">
                        <div class="flex items-center justify-between px-space-lg py-space-md">
                            <span class="font-body-md text-body-md text-on-surface">{{ $school->name }}</span>
                            <div class="flex items-center gap-space-md">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="font-label-md text-label-md text-primary hover:text-primary-fixed-variant transition-colors"
                                >
                                    <span x-text="open ? 'Hide Tier' : 'Manage Tier'"></span>
                                </button>
                                <a href="{{ $schoolUrl }}" class="font-label-md text-label-md text-primary hover:text-primary-fixed-variant transition-colors">
                                    {{ $school->domain }}
                                </a>
                            </div>
                        </div>

                        <div x-show="open" x-cloak x-transition class="px-space-lg pb-space-lg space-y-space-lg bg-surface-container-lowest">
                            @if ($tierOverview['isDemoMode'])
                                <div class="p-space-md bg-warning/10 border border-warning/20 rounded-lg flex items-center gap-space-md">
                                    <span class="material-symbols-outlined text-warning text-[20px]" data-weight="fill">info</span>
                                    <p class="font-body-sm text-body-sm text-warning">This is a demo account. Tier management is read-only.</p>
                                </div>
                            @endif

                            <div class="p-space-md bg-surface border border-outline-variant rounded-lg space-y-space-sm">
                                <p class="font-label-md text-label-md text-secondary uppercase">Current Tier</p>
                                <div class="flex items-center gap-space-lg">
                                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ $tierOverview['currentTier']->name }}</p>
                                    <p class="font-body-md text-body-md text-secondary">
                                        @if ($tierOverview['currentTier']->price == 0)
                                            Free
                                        @else
                                            Rp {{ number_format((int) $tierOverview['currentTier']->price) }}/{{ $tierOverview['currentTier']->billing_period->label() }}
                                        @endif
                                    </p>
                                </div>

                                @php
                                    $currentStorage = $tierOverview['currentTier']->limits->firstWhere('limit_key', \App\Enums\TierLimit::MaterialStorageGb->value);
                                @endphp
                                <p class="font-body-sm text-body-sm text-secondary">
                                    Storage: {{ $currentStorage?->limit_value !== null ? $currentStorage->limit_value.' GB' : 'Unlimited' }}
                                </p>
                            </div>

                            @if ($tierOverview['upgradeTiers']->count() > 0)
                                <div class="space-y-space-sm">
                                    <p class="font-label-md text-label-md text-secondary uppercase">Available Upgrades</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-md">
                                        @foreach ($tierOverview['upgradeTiers'] as $tier)
                                            <div class="p-space-md bg-surface border border-outline-variant rounded-lg flex flex-col gap-space-sm">
                                                <p class="font-headline-sm text-headline-sm text-on-surface">{{ $tier->name }}</p>
                                                <p class="font-body-md text-body-md text-secondary">
                                                    @if ($tier->price == 0)
                                                        Free
                                                    @else
                                                        Rp {{ number_format((int) $tier->price) }}/{{ $tier->billing_period->label() }}
                                                    @endif
                                                </p>

                                                @php
                                                    $chargeAmount = $tierOverview['chargeAmounts'][$tier->id] ?? (float) $tier->price;
                                                    $upgradeStorage = $tier->limits->firstWhere('limit_key', \App\Enums\TierLimit::MaterialStorageGb->value);
                                                @endphp
                                                <p class="font-body-sm text-body-sm text-secondary">
                                                    Storage: {{ $upgradeStorage?->limit_value !== null ? $upgradeStorage->limit_value.' GB' : 'Unlimited' }}
                                                </p>
                                                <p class="font-body-sm text-body-sm text-info">
                                                    Charge now: Rp {{ number_format((int) $chargeAmount) }}
                                                </p>

                                                @if ($tierOverview['isDemoMode'])
                                                    <p class="font-body-sm text-body-sm text-secondary">Demo accounts cannot upgrade</p>
                                                @elseif ($tierOverview['enabledGateways']->count() > 0)
                                                    <button
                                                        type="button"
                                                        wire:click="changeTier('{{ $school->id }}', '{{ $tier->id }}')"
                                                        class="mt-auto w-full px-space-md py-space-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity"
                                                    >
                                                        Upgrade Now
                                                    </button>
                                                @else
                                                    <p class="font-body-sm text-body-sm text-error">No payment gateway configured</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($tierOverview['downgradeTiers']->count() > 0)
                                <div class="space-y-space-sm">
                                    <p class="font-label-md text-label-md text-secondary uppercase">Available Downgrades</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-md">
                                        @foreach ($tierOverview['downgradeTiers'] as $tier)
                                            <div class="p-space-md bg-surface border border-outline-variant rounded-lg flex flex-col gap-space-sm">
                                                <p class="font-headline-sm text-headline-sm text-on-surface">{{ $tier->name }}</p>
                                                <p class="font-body-md text-body-md text-secondary">
                                                    @if ($tier->price == 0)
                                                        Free
                                                    @else
                                                        Rp {{ number_format((int) $tier->price) }}/{{ $tier->billing_period->label() }}
                                                    @endif
                                                </p>

                                                @php
                                                    $proration = $tierOverview['prorations'][$tier->id] ?? 0;
                                                    $downgradeStorage = $tier->limits->firstWhere('limit_key', \App\Enums\TierLimit::MaterialStorageGb->value);
                                                @endphp
                                                <p class="font-body-sm text-body-sm text-secondary">
                                                    Storage: {{ $downgradeStorage?->limit_value !== null ? $downgradeStorage->limit_value.' GB' : 'Unlimited' }}
                                                </p>
                                                @if ($proration != 0)
                                                    <p class="font-body-sm text-body-sm text-success">
                                                        @if ($proration < 0)
                                                            Refund: Rp {{ number_format((int) abs($proration)) }}
                                                        @else
                                                            Additional charge: Rp {{ number_format((int) $proration) }}
                                                        @endif
                                                    </p>
                                                @endif

                                                @if ($tierOverview['isDemoMode'])
                                                    <p class="font-body-sm text-body-sm text-secondary">Demo accounts cannot downgrade</p>
                                                @elseif ($tierOverview['enabledGateways']->count() > 0)
                                                    <button
                                                        type="button"
                                                        wire:click="changeTier('{{ $school->id }}', '{{ $tier->id }}')"
                                                        class="mt-auto w-full px-space-md py-space-xs bg-secondary text-on-secondary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity"
                                                    >
                                                        Downgrade
                                                    </button>
                                                @else
                                                    <p class="font-body-sm text-body-sm text-error">No payment gateway configured</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
