@section('title', 'Billing Settings')

<div class="space-y-space-lg max-w-2xl">
    <div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Billing Settings</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">Configure the VAT and admin fee rates applied to tier registrations</p>
    </div>

    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    <form wire:submit="update" x-data="{ adminFeeType: @js($admin_fee_type) }" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <div class="space-y-space-md">
            <label for="vat_rate" class="block font-label-md text-label-md text-on-surface">VAT / PPN (%)</label>
            <input type="number" id="vat_rate" wire:model="vat_rate" step="0.01" min="0" max="100" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
            @error('vat_rate')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-space-md">
            <label for="admin_fee_type" class="block font-label-md text-label-md text-on-surface">Admin Fee Type</label>
            <select id="admin_fee_type" wire:model="admin_fee_type" x-model="adminFeeType" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
                @foreach ($adminFeeTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('admin_fee_type')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div x-show="adminFeeType === '{{ \App\Enums\AdminFeeType::Percentage->value }}'" class="space-y-space-md">
            <label for="admin_fee_rate" class="block font-label-md text-label-md text-on-surface">Admin Fee (%)</label>
            <input type="number" id="admin_fee_rate" wire:model="admin_fee_rate" step="0.01" min="0" max="100" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
            @error('admin_fee_rate')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div x-show="adminFeeType === '{{ \App\Enums\AdminFeeType::Fixed->value }}'" class="space-y-space-md">
            <label for="admin_fee_nominal" class="block font-label-md text-label-md text-on-surface">Admin Fee (Rp)</label>
            <input type="number" id="admin_fee_nominal" wire:model="admin_fee_nominal" step="1" min="0" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary" />
            @error('admin_fee_nominal')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-space-md">
            <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                Save Changes
            </button>
        </div>
    </form>
</div>
