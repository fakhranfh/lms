@section('title', 'Edit Pricing Tier')

<div class="space-y-space-lg max-w-2xl">
    <div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Edit Pricing Tier</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">Update pricing tier configuration</p>
    </div>

    <form wire:submit="update" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <div class="space-y-space-md">
            <label for="name" class="block font-label-md text-label-md text-on-surface">Name</label>
            <input type="text" id="name" wire:model="name" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
            @error('name')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="bg-surface-container px-space-md py-space-sm rounded-lg">
            <p class="font-body-sm text-on-surface-variant">Slug: <span class="font-medium text-on-surface">{{ $slug }}</span></p>
        </div>

        <div class="space-y-space-md">
            <label for="description" class="block font-label-md text-label-md text-on-surface">Description</label>
            <textarea id="description" wire:model="description" rows="3" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            @error('description')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-space-md">
            <label for="price" class="block font-label-md text-label-md text-on-surface">Price (Rp)</label>
            <input type="number" id="price" wire:model="price" step="1" min="0" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
            @error('price')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
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
                <h3 class="font-label-md text-label-md text-on-surface mb-space-md">Limits</h3>
                <div class="space-y-space-md">
                    @foreach ($availableLimits as $limit)
                        <div class="space-y-space-sm">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-body-sm text-body-sm text-on-surface">{{ $limit->label() }}</p>
                                    <p class="font-body-xs text-body-xs text-on-surface-variant">{{ $limit->description() }}</p>
                                </div>
                            </div>
                            <input type="number" wire:model="limits.{{ $loop->index }}.limit_value" name="limits[{{ $loop->index }}][limit_value]" placeholder="Leave empty for unlimited" min="0" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
                            <input type="hidden" name="limits[{{ $loop->index }}][limit_key]" value="{{ $limit->value }}" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex gap-space-md">
            <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Update Tier
            </button>
            <a href="{{ route('admin.pricing-tiers.index') }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                Cancel
            </a>
        </div>
    </form>
</div>
