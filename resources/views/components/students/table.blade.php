@props([
    'students',
    'studentsLoaded' => true,
    'sort' => 'name',
    'direction' => 'asc',
])

<div class="bg-surface rounded-lg border border-outline-variant overflow-hidden">
    @unless ($studentsLoaded)
        @include('components.students.partials.table-skeleton')
    @else
        <div wire:loading.block wire:target="search,sortBy,perPage,gotoPage,previousPage,nextPage">
            @include('components.students.partials.table-skeleton')
        </div>

        <div wire:loading.remove wire:target="search,sortBy,perPage,gotoPage,previousPage,nextPage" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-container border-b border-outline-variant">
                    <tr>
                        @can('students.delete')
                            <th class="px-space-lg py-space-md w-[1%]">
                                <input type="checkbox" :checked="allOnPageSelected" @change="toggleSelectAll($event.target.checked)" aria-label="Select all students on this page">
                            </th>
                        @endcan
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('name')">
                            <span class="inline-flex items-center gap-space-2xs">
                                Name
                                @if ($sort === 'name')
                                    <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                @else
                                    <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                                @endif
                            </span>
                        </th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface cursor-pointer select-none hover:bg-surface-container-lowest transition-colors" wire:click="sortBy('email')">
                            <span class="inline-flex items-center gap-space-2xs">
                                Email
                                @if ($sort === 'email')
                                    <span class="material-symbols-outlined text-[16px] text-primary">{{ $direction === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                @else
                                    <span class="material-symbols-outlined text-[16px] text-on-surface/30">unfold_more</span>
                                @endif
                            </span>
                        </th>
                        <th class="px-space-lg py-space-md text-right font-label-md text-label-md text-on-surface">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @can('students.delete')
                        <tr x-show="selected.length > 0" x-cloak>
                            <td colspan="4" class="px-space-lg py-space-sm bg-surface-container">
                                <div class="flex items-center justify-between gap-space-md flex-wrap">
                                    <div class="flex items-center gap-space-md flex-wrap">
                                        <template x-if="!selectAllMatching">
                                            <p class="font-label-md text-label-md text-on-surface"><span x-text="selected.length"></span> selected</p>
                                        </template>

                                        <template x-if="!selectAllMatching && allOnPageSelected && matchingCount > selected.length">
                                            <button type="button" @click="selectAllMatching = true" class="font-label-sm text-label-sm text-primary hover:underline">
                                                <span x-text="`Select all ${matchingCount} students`"></span>
                                            </button>
                                        </template>

                                        <template x-if="selectAllMatching">
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                                <span x-text="`All ${matchingCount} students selected.`"></span>
                                                <button type="button" @click="clearSelection()" class="text-primary hover:underline">Clear selection</button>
                                            </p>
                                        </template>
                                    </div>

                                    <div class="flex items-center gap-space-sm">
                                        <button
                                            type="button"
                                            @click="clearSelection()"
                                            class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface transition"
                                        >
                                            Clear
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteId = null; showDeleteModal = true"
                                            class="px-space-md py-space-xs bg-error text-on-error rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity inline-flex items-center gap-space-xs"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                            Delete Selected
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endcan
                    @forelse ($students as $student)
                        <x-students.partials.table-row :student="$student" />
                    @empty
                        <tr>
                            <td colspan="4" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                                No students found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endunless
</div>
