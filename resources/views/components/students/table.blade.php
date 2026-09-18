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
        <div wire:loading.block wire:target="search,sortBy,perPage">
            @include('components.students.partials.table-skeleton')
        </div>

        <div wire:loading.remove wire:target="search,sortBy,perPage" class="overflow-x-auto">
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
