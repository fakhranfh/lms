@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    @if ($successMessage)
        <div wire:click="clearSuccessMessage" class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md cursor-pointer">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    <div class="flex items-start justify-between">
        <h1 class="font-headline-md text-headline-md text-on-surface">Gradebook</h1>
    </div>

    @if (! $isStudent)
        <!-- Teacher: per-student Final Score roster -->
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container/50">
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Student</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Final Score</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Grade</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($studentRows as $row)
                            <tr
                                wire:key="roster-{{ $row['user']->id }}"
                                wire:click="selectStudent('{{ $row['user']->id }}')"
                                class="cursor-pointer transition-colors {{ $selectedStudent && $selectedStudent->id === $row['user']->id ? 'bg-primary/5' : 'hover:bg-surface-container/50' }}"
                            >
                                <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">{{ $row['user']->name }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['final']['score'] !== null ? number_format($row['final']['score'], 2) : '—' }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['final']['grade'] ?? '—' }}</td>
                                <td class="px-space-lg py-space-md text-body-xs text-on-surface-variant">{{ $row['final']['last_updated_at']?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No students enrolled yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($selectedStudent)
            <h2 class="font-title-md text-title-md text-on-surface">{{ $selectedStudent->name }}'s Breakdown</h2>
        @endif
    @endif

    @if ($result)
        <!-- Final Score card -->
        <div class="grid grid-cols-3 gap-space-md">
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Final Score</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ $result['final']['score'] !== null ? number_format($result['final']['score'], 2) : '—' }}</p>
            </div>
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Grade</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ $result['final']['grade'] ?? '—' }}</p>
            </div>
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Last Updated</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ $result['final']['last_updated_at']?->diffForHumans() ?? '—' }}</p>
            </div>
        </div>

        <!-- Assessment Type breakdown -->
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
            @forelse ($typeRows as $typeRow)
                <div
                    wire:key="type-{{ $typeRow['key'] }}"
                    @if ($typeRow['expandable'])
                        x-data="{
                            expanded: false,
                            loading: false,
                            loaded: false,
                            sessions: [],
                            async toggle() {
                                this.expanded = !this.expanded;
                                if (this.expanded && !this.loaded) {
                                    this.loading = true;
                                    try {
                                        const response = await fetch('{{ $typeRow['sessions_url'] }}', { headers: { 'Accept': 'application/json' } });
                                        const data = await response.json();
                                        this.sessions = data.sessions;
                                        this.loaded = true;
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            },
                        }"
                    @endif
                >
                    <div
                        @if ($typeRow['expandable']) @click="toggle()" @endif
                        class="p-space-lg flex items-center gap-space-md {{ $typeRow['expandable'] ? 'cursor-pointer hover:bg-surface-container/50' : '' }}"
                    >
                        <div class="flex-1">
                            <p class="font-label-md text-label-md text-on-surface">{{ $typeRow['label'] }}</p>
                            <p class="text-body-xs text-on-surface-variant">Weight {{ number_format($typeRow['weight'], 2) }}%</p>
                        </div>
                        <div class="text-right">
                            <p class="font-label-md text-label-md text-on-surface">{{ $typeRow['score'] !== null ? number_format($typeRow['score'], 2) : 'Not graded yet' }}</p>
                            <p class="text-body-xs text-on-surface-variant">{{ $typeRow['last_updated_at']?->diffForHumans() ?? '' }}</p>
                        </div>
                        @if ($typeRow['expandable'])
                            <span class="material-symbols-outlined text-on-surface-variant text-[20px]" x-text="expanded ? 'expand_less' : 'expand_more'"></span>
                        @endif
                    </div>

                    @if ($typeRow['expandable'])
                        <div x-show="expanded" x-cloak class="bg-surface-container/30 px-space-lg pb-space-md">
                            <template x-if="loading">
                                <div class="animate-pulse space-y-space-xs py-space-sm">
                                    <div class="h-4 bg-surface-container rounded w-full"></div>
                                    <div class="h-4 bg-surface-container rounded w-full"></div>
                                    <div class="h-4 bg-surface-container rounded w-2/3"></div>
                                </div>
                            </template>
                            <template x-if="!loading">
                                <table class="w-full">
                                    <thead>
                                        <tr>
                                            <th class="py-space-xs text-left font-label-sm text-label-sm text-on-surface-variant">Session</th>
                                            <th class="py-space-xs text-left font-label-sm text-label-sm text-on-surface-variant">Weight</th>
                                            <th class="py-space-xs text-left font-label-sm text-label-sm text-on-surface-variant">Score</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant">
                                        <template x-for="row in sessions" :key="row.index">
                                            <tr>
                                                <td class="py-space-xs text-body-sm text-on-surface" x-text="'Session ' + row.index + ' – ' + row.delivery_mode"></td>
                                                <td class="py-space-xs text-body-sm text-on-surface" x-text="row.weight.toFixed(2) + '%'"></td>
                                                <td class="py-space-xs text-body-sm text-on-surface" x-text="row.score.toFixed(2)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </template>
                        </div>
                    @endif
                </div>
            @empty
                <p class="p-space-lg text-center text-body-sm text-on-surface-variant">No assessments yet.</p>
            @endforelse
        </div>
    @endif

    <!-- Grading Scale -->
    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
        <div wire:click="$toggle('showGradeScales')" class="p-space-lg flex items-center justify-between cursor-pointer hover:bg-surface-container/50">
            <h2 class="font-title-md text-title-md text-on-surface">Grading Scale</h2>
            <span class="material-symbols-outlined text-on-surface-variant text-[20px]">{{ $showGradeScales ? 'expand_less' : 'expand_more' }}</span>
        </div>

        @if ($showGradeScales)
            <div class="border-t border-outline-variant p-space-lg space-y-space-md">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="py-space-xs text-left font-label-sm text-label-sm text-on-surface-variant">Label</th>
                            <th class="py-space-xs text-left font-label-sm text-label-sm text-on-surface-variant">Min</th>
                            <th class="py-space-xs text-left font-label-sm text-label-sm text-on-surface-variant">Max</th>
                            @if ($canManage)
                                <th class="py-space-xs text-left font-label-sm text-label-sm text-on-surface-variant">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($gradeScales as $scale)
                            <tr wire:key="scale-{{ $scale->id }}">
                                <td class="py-space-xs text-body-sm text-on-surface">{{ $scale->label }}</td>
                                <td class="py-space-xs text-body-sm text-on-surface">{{ $scale->score_min }}</td>
                                <td class="py-space-xs text-body-sm text-on-surface">{{ $scale->score_max }}</td>
                                @if ($canManage)
                                    <td class="py-space-xs">
                                        <button type="button" wire:click="startEditScale('{{ $scale->id }}')" class="text-primary font-label-sm text-label-sm hover:underline">Edit</button>
                                        <button type="button" wire:click="deleteGradeScale('{{ $scale->id }}')" wire:confirm="Delete this grading scale?" class="text-error font-label-sm text-label-sm hover:underline ml-space-sm">Delete</button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 4 : 3 }}" class="py-space-md text-center text-body-sm text-on-surface-variant">No grading scale defined.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($canManage)
                    <form wire:submit.prevent="saveGradeScale" class="flex items-end gap-space-sm">
                        <div>
                            <label class="block text-body-xs text-on-surface-variant mb-space-xs">Label</label>
                            <input type="text" wire:model="scaleLabel" class="px-space-sm py-1 border border-outline rounded-lg font-body-sm text-body-sm w-20 focus:outline-none focus:ring-2 focus:ring-primary/50" />
                            @error('scaleLabel') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-body-xs text-on-surface-variant mb-space-xs">Min</label>
                            <input type="number" wire:model="scaleMin" class="px-space-sm py-1 border border-outline rounded-lg font-body-sm text-body-sm w-20 focus:outline-none focus:ring-2 focus:ring-primary/50" />
                            @error('scaleMin') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-body-xs text-on-surface-variant mb-space-xs">Max</label>
                            <input type="number" wire:model="scaleMax" class="px-space-sm py-1 border border-outline rounded-lg font-body-sm text-body-sm w-20 focus:outline-none focus:ring-2 focus:ring-primary/50" />
                            @error('scaleMax') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                        </div>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="saveGradeScale"
                            class="px-space-md py-1.5 bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-xs"
                        >
                            <span wire:loading wire:target="saveGradeScale" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                            {{ $editingScaleId ? 'Update' : 'Add' }}
                        </button>
                        @if ($editingScaleId)
                            <button type="button" wire:click="cancelScaleForm" class="px-space-md py-1.5 bg-surface-container rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity">Cancel</button>
                        @endif
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
