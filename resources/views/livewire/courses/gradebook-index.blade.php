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
                                <td class="px-space-lg py-space-md text-body-xs text-on-surface-variant">{{ $row['final']['last_updated_at']?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No students enrolled yet.</td>
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
        <!-- Final Score banner -->
        <div class="rounded-lg p-space-lg bg-primary grid grid-cols-[1fr_4rem_4rem_4rem] gap-space-lg items-start">
            <div class="min-w-0">
                <p class="font-title-md text-title-md text-on-primary">Final Score</p>
                @if ($finalLastUpdatedLabel)
                    <p class="text-body-xs text-on-primary/80">Last updated: {{ $finalLastUpdatedLabel }}</p>
                @endif
            </div>
            <div class="text-center">
                <p class="text-body-xs text-on-primary/80">Weight</p>
                <div class="mt-space-xs w-11 h-11 mx-auto rounded-full bg-black/20 flex items-center justify-center">
                    <span class="font-label-sm text-label-sm text-on-primary">100%</span>
                </div>
            </div>
            <div class="text-center">
                <p class="text-body-xs text-on-primary/80">Score</p>
                <p class="font-headline-sm text-headline-sm text-on-primary mt-space-xs">{{ $result['final']['score'] !== null ? number_format($result['final']['score'], 0) : '—' }}</p>
            </div>
            <div class="text-center">
                <p class="text-body-xs text-on-primary/80">Grade</p>
                <p class="font-headline-sm text-headline-sm text-on-primary mt-space-xs">{{ $finalGrade ?? '—' }}</p>
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
                            items: [],
                            async load() {
                                if (this.loaded) {
                                    return;
                                }
                                this.loading = true;
                                try {
                                    const response = await fetch('{{ $typeRow['sessions_url'] }}', { headers: { 'Accept': 'application/json' } });
                                    const data = await response.json();
                                    this.items = data.items;
                                    this.loaded = true;
                                } finally {
                                    this.loading = false;
                                }
                            },
                        }"
                    @endif
                >
                    <div
                        @if ($typeRow['expandable']) @click="expanded = !expanded; load()" @endif
                        class="p-space-lg grid grid-cols-[1fr_4rem_4rem_4rem] gap-space-lg items-center {{ $typeRow['expandable'] ? 'cursor-pointer' : '' }}"
                    >
                        <div class="min-w-0">
                            <p class="font-title-sm text-title-sm text-on-surface flex items-center gap-space-xs">
                                {{ $typeRow['label'] }}
                                @if ($typeRow['expandable'])
                                    <span class="material-symbols-outlined text-on-surface-variant text-[18px]" x-text="expanded ? 'expand_less' : 'expand_more'"></span>
                                @endif
                            </p>
                            @if ($typeRow['last_updated_label'])
                                <p class="text-body-xs text-on-surface-variant">Last updated: {{ $typeRow['last_updated_label'] }}</p>
                            @endif
                        </div>
                        <div class="text-center">
                            <div class="w-11 h-11 mx-auto rounded-full bg-surface-container flex items-center justify-center">
                                <span class="font-label-sm text-label-sm text-on-surface">{{ number_format($typeRow['weight'], 0) }}%</span>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="font-title-sm text-title-sm text-on-surface">{{ $typeRow['score'] !== null ? number_format($typeRow['score'], 0) : '—' }}</p>
                        </div>
                    </div>

                    @if ($typeRow['expandable'])
                        <div x-show="expanded" x-cloak class="px-space-lg pb-space-lg">
                            <div class="bg-surface-container/40 border border-t-0 border-outline-variant rounded-b-lg overflow-hidden">
                                <template x-if="loading">
                                    <div class="animate-pulse space-y-space-xs p-space-md">
                                        <div class="h-4 bg-surface rounded w-full"></div>
                                        <div class="h-4 bg-surface rounded w-full"></div>
                                        <div class="h-4 bg-surface rounded w-2/3"></div>
                                    </div>
                                </template>
                                <template x-if="!loading">
                                    <div class="divide-y divide-outline-variant">
                                        <template x-for="row in items" :key="row.label">
                                            <div class="pl-space-lg py-space-sm grid grid-cols-[1fr_4rem_4rem_4rem] gap-space-lg items-center">
                                                <p class="min-w-0 font-label-md text-label-md text-on-surface" x-text="row.label"></p>
                                                <div class="text-center">
                                                    <div class="w-8 h-8 mx-auto rounded-full bg-surface-container flex items-center justify-center">
                                                        <span class="text-[10px] leading-none tracking-normal text-on-surface text-label-sm" x-text="Math.round(row.weight) + '%'"></span>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <p class="font-title-sm text-title-sm text-on-surface" x-text="row.score !== null ? Math.round(row.score) : '—'"></p>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="items.length === 0">
                                            <p class="px-space-lg py-space-sm text-center text-body-sm text-on-surface-variant">No data yet.</p>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="p-space-lg text-center text-body-sm text-on-surface-variant">No assessments yet.</p>
            @endforelse
        </div>
    @endif
</div>
