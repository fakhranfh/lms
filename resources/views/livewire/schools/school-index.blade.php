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

    <!-- Table -->
    <div class="bg-surface rounded-lg border border-outline-variant overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-container border-b border-outline-variant">
                    <tr>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">School Name</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Domain</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Tier</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Created</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($schools as $school)
                        <tr class="hover:bg-surface-container-lowest transition-colors">
                            <td class="px-space-lg py-space-md text-body-md text-on-surface">
                                <a href="{{ route('admin.schools.edit', $school) }}" class="text-primary hover:underline">
                                    {{ $school->name }}
                                </a>
                            </td>
                            <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $school->domain }}</td>
                            <td class="px-space-lg py-space-md">
                                <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-sm text-label-sm bg-primary-container text-on-primary-container">
                                    {{ $school->tier->name }}
                                </span>
                            </td>
                            <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">
                                {{ $school->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-space-lg py-space-md">
                                <div class="flex gap-space-sm">
                                    <a href="{{ route('admin.schools.edit', $school) }}" class="px-space-md py-space-xs rounded-lg bg-primary text-on-primary font-label-sm text-label-sm hover:bg-on-primary-fixed-variant transition-colors">
                                        Edit
                                    </a>
                                    <a href="{{ route('admin.schools.tier-history', $school) }}" class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors">
                                        History
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                                No schools found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $schools->links() }}
    </div>
</div>
