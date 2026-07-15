<div class="space-y-space-lg">
    <div class="flex items-center justify-between">
        <h1 class="text-headline-lg font-headline-lg">Edit School: {{ $this->school->name }}</h1>
        <a href="{{ route('admin.schools.index') }}" class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors">
            Back to Schools
        </a>
    </div>

    <!-- Messages -->
    @if($successMessage)
        <div class="mb-space-md rounded-lg border border-green-200 bg-green-50 px-space-lg py-space-md text-green-700 font-body-sm">
            {{ $successMessage }}
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-space-md rounded-lg border border-error bg-error/10 px-space-lg py-space-md text-error font-body-sm">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-space-lg">
        <!-- School Details -->
        <div class="lg:col-span-2 space-y-space-lg">
            <div class="bg-surface rounded-lg border border-outline-variant p-space-lg">
                <h2 class="text-headline-sm font-headline-sm mb-space-lg text-on-surface">School Details</h2>
                <div class="space-y-space-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-space-xs">Name</label>
                        <p class="text-body-md text-on-surface">{{ $this->school->name }}</p>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-space-xs">Domain</label>
                        <p class="text-body-md text-on-surface">{{ $this->school->domain }}</p>
                    </div>
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface-variant mb-space-xs">Created</label>
                        <p class="text-body-md text-on-surface">{{ $this->school->created_at->format('F d, Y h:i A') }}</p>
                    </div>
                </div>
            </div>

            <!-- Current Tier Information -->
            <div class="bg-surface rounded-lg border border-outline-variant p-space-lg">
                <h2 class="text-headline-sm font-headline-sm mb-space-lg text-on-surface">Current Tier</h2>

                <div class="mb-space-lg">
                    <div class="flex items-center justify-between mb-space-md">
                        <h3 class="text-title-md font-title-md text-on-surface">{{ $this->school->tier->name }}</h3>
                        <span class="inline-block px-space-md py-space-xs rounded-full font-label-sm text-label-sm bg-primary-container text-on-primary-container">
                            IDR {{ number_format($this->school->tier->price, 0) }}/{{ $this->school->tier->billing_period->value }}
                        </span>
                    </div>
                    <p class="text-body-sm text-on-surface-variant">{{ $this->school->tier->description }}</p>
                </div>

                <!-- Features -->
                @if($this->tierFeatures->count() > 0)
                    <div class="mb-space-lg">
                        <h4 class="font-label-md text-label-md text-on-surface mb-space-sm">Features</h4>
                        <ul class="space-y-space-xs">
                            @foreach($this->tierFeatures as $feature)
                                @if($feature->is_enabled)
                                    <li class="flex items-center gap-space-sm text-body-sm text-on-surface">
                                        <span class="w-5 h-5 flex items-center justify-center rounded bg-primary text-on-primary text-xs">✓</span>
                                        {{ ucfirst(str_replace('_', ' ', $feature->feature_key)) }}
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Limits -->
                @if($this->tierLimits->count() > 0)
                    <div>
                        <h4 class="font-label-md text-label-md text-on-surface mb-space-sm">Limits</h4>
                        <div class="space-y-space-xs">
                            @foreach($this->tierLimits as $limit)
                                <div class="flex justify-between text-body-sm">
                                    <span class="text-on-surface">{{ ucfirst(str_replace('_', ' ', $limit->limit_key)) }}</span>
                                    <span class="font-medium text-on-surface">
                                        @if($limit->limit_value === null)
                                            Unlimited
                                        @else
                                            {{ number_format($limit->limit_value) }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tier Change Section -->
        <div class="space-y-space-lg">
            <div class="bg-surface rounded-lg border border-outline-variant p-space-lg sticky top-space-lg">
                <h2 class="text-headline-sm font-headline-sm mb-space-lg text-on-surface">Change Tier</h2>

                <div class="space-y-space-md">
                    <div>
                        <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Select New Tier</label>
                        <select wire:model="newTierId" class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
                            <option value="">-- Select Tier --</option>
                            @foreach($this->availableTiers as $tier)
                                <option value="{{ $tier->id }}">
                                    {{ $tier->name }} - IDR {{ number_format($tier->price, 0) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button wire:click="initiateChange" class="w-full h-[44px] mt-space-lg bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed" {{ !$newTierId ? 'disabled' : '' }}>
                        Change Tier
                    </button>
                </div>

                <!-- Subscription Info -->
                @if($this->currentSchoolTier)
                    <div class="mt-space-lg pt-space-lg border-t border-outline-variant">
                        <h4 class="font-label-md text-label-md text-on-surface mb-space-sm">Subscription Status</h4>
                        <div class="space-y-space-xs text-body-sm">
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant">Status:</span>
                                <span class="font-medium text-on-surface">{{ ucfirst($this->currentSchoolTier->status->value) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant">Started:</span>
                                <span class="font-medium text-on-surface">{{ $this->currentSchoolTier->started_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant">Auto Renew:</span>
                                <span class="font-medium text-on-surface">{{ $this->currentSchoolTier->auto_renew ? 'Yes' : 'No' }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    @if($showChangeConfirmation)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-surface rounded-lg p-space-xl max-w-[420px] shadow-lg">
                <h3 class="text-title-md font-title-md text-on-surface mb-space-md">Confirm Tier Change</h3>
                <p class="text-body-md text-on-surface-variant mb-space-lg">
                    Are you sure you want to change the tier from <strong>{{ $this->school->tier->name }}</strong> to <strong>{{ $this->availableTiers->find('id', $this->newTierId)?->name }}</strong>?
                </p>
                <div class="flex gap-space-md">
                    <button wire:click="cancelChange" class="flex-1 h-[44px] rounded-lg border border-outline-variant text-on-surface font-label-md text-label-md hover:bg-surface-container transition-colors">
                        Cancel
                    </button>
                    <button wire:click="confirmChange" class="flex-1 h-[44px] bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-on-primary-fixed-variant transition-colors">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
