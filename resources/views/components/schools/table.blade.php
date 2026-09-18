@props([
    'schools',
    'sort' => 'name',
    'direction' => 'asc',
    'perPage' => 15,
])

<x-ui.livewire-data-table
    :columns="[
        ['key' => 'name', 'label' => 'School Name'],
        ['key' => 'domain', 'label' => 'Domain'],
        ['key' => 'tier', 'label' => 'Tier', 'sortable' => false],
        ['key' => 'created_at', 'label' => 'Created'],
    ]"
    :items="$schools"
    :sort="$sort"
    :direction="$direction"
    :perPage="$perPage"
    loadingTarget="search,filterTier,sortBy,perPage"
>
    @forelse ($schools as $school)
        <tr wire:key="school-{{ $school->id }}" class="hover:bg-surface-container-lowest transition-colors">
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
                {{ $school->created_at_display->format('M d, Y') }}
            </td>
            <td class="px-space-lg py-space-md text-right whitespace-nowrap">
                <div class="flex justify-end gap-space-sm">
                    <a href="{{ route('admin.schools.edit', $school) }}" class="px-space-md py-space-xs rounded-lg bg-primary text-on-primary font-label-sm text-label-sm hover:bg-on-primary-fixed-variant transition-colors">
                        Edit
                    </a>
                    <a href="{{ route('admin.schools.tier-history', $school) }}" class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors">
                        History
                    </a>
                    @unless ($school->domain === config('app.domain'))
                        <button type="button" @click="deleteId = '{{ $school->id }}'; showModal = true" class="px-space-md py-space-xs rounded-lg bg-error text-on-error font-label-sm text-label-sm hover:opacity-90 transition-opacity">
                            Delete
                        </button>
                    @endunless
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
</x-ui.livewire-data-table>
