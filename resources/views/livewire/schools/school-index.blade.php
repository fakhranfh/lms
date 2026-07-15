@section('title', 'Schools')

<div class="space-y-space-lg">
    <div class="flex items-center justify-between">
        <h1 class="text-headline-lg font-headline-lg">Schools Management</h1>
    </div>

    <!-- Search and Filter -->
    <div class="bg-surface rounded-lg p-space-lg border border-outline-variant">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Search</label>
                <input type="text" wire:model.live="search" placeholder="Search by name or domain..."
                    class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
            </div>
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Filter by Tier</label>
                <select wire:model.live="filterTier" class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
                    <option value="">All Tiers</option>
                    @foreach($this->availableTiers as $tier)
                        <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Schools Table Component -->
    <x-schools.table :schools="$schools" :sort="$sort" :direction="$direction" :perPage="$perPage" />
</div>
