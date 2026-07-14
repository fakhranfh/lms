@section('title', 'Create Pricing Tier')

<div class="space-y-space-lg max-w-2xl">
    <div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Create Pricing Tier</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">Add a new pricing tier to the system</p>
    </div>

    <form wire:submit="store" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <div class="space-y-space-md">
            <label for="name" class="block font-label-md text-label-md text-on-surface">Name</label>
            <input type="text" id="name" wire:model="name" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
            @error('name')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-space-md">
            <label for="slug" class="block font-label-md text-label-md text-on-surface">Slug</label>
            <input type="text" id="slug" wire:model="slug" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
            @error('slug')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-space-md">
            <label for="description" class="block font-label-md text-label-md text-on-surface">Description</label>
            <textarea id="description" wire:model="description" rows="3" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            @error('description')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-space-lg">
            <div class="space-y-space-md">
                <label for="price" class="block font-label-md text-label-md text-on-surface">Price</label>
                <input type="number" id="price" wire:model="price" step="0.01" min="0" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
                @error('price')
                    <p class="text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-space-md">
                <label for="currency" class="block font-label-md text-label-md text-on-surface">Currency</label>
                <input type="text" id="currency" wire:model="currency" maxlength="3" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
                @error('currency')
                    <p class="text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="space-y-space-md">
            <label for="billing_period" class="block font-label-md text-label-md text-on-surface">Billing Period</label>
            <select id="billing_period" wire:model="billing_period" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
                @foreach ($billingPeriods as $period)
                    <option value="{{ $period->value }}">{{ $period->label() }}</option>
                @endforeach
            </select>
            @error('billing_period')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-space-md">
            <label class="flex items-center gap-space-sm">
                <input type="checkbox" wire:model="is_active" class="w-4 h-4 rounded border-outline" />
                <span class="font-label-md text-label-md text-on-surface">Active</span>
            </label>
        </div>

        <div class="border-t border-outline-variant pt-space-lg space-y-space-lg">
            <div>
                <div class="flex items-center justify-between mb-space-md">
                    <h3 class="font-label-md text-label-md text-on-surface">Features</h3>
                    <button type="button" wire:click="addFeature" class="text-label-md text-label-md text-primary hover:underline">Add Feature</button>
                </div>

                <div class="space-y-space-sm">
                    @foreach ($features as $index => $feature)
                        <div class="flex gap-space-md">
                            <input type="text" wire:model="features.{{ $index }}.feature_key" placeholder="Feature key" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
                            <label class="flex items-center gap-space-sm">
                                <input type="checkbox" wire:model="features.{{ $index }}.is_enabled" class="w-4 h-4 rounded border-outline" />
                                <span class="font-body-sm text-body-sm text-on-surface">Enabled</span>
                            </label>
                            <button type="button" wire:click="removeFeature({{ $index }})" class="text-error hover:underline">Remove</button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-space-md">
                    <h3 class="font-label-md text-label-md text-on-surface">Limits</h3>
                    <button type="button" wire:click="addLimit" class="text-label-md text-label-md text-primary hover:underline">Add Limit</button>
                </div>

                <div class="space-y-space-sm">
                    @foreach ($limits as $index => $limit)
                        <div class="flex gap-space-md">
                            <input type="text" wire:model="limits.{{ $index }}.limit_key" placeholder="Limit key" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
                            <input type="number" wire:model="limits.{{ $index }}.limit_value" placeholder="Value (leave empty for unlimited)" min="0" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
                            <button type="button" wire:click="removeLimit({{ $index }})" class="text-error hover:underline">Remove</button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex gap-space-md">
            <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Create Tier
            </button>
            <a href="{{ route('admin.pricing-tiers.index') }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                Cancel
            </a>
        </div>
    </form>
</div>
