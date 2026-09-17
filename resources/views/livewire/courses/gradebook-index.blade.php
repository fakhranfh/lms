@section('title', $course->title)

<div class="space-y-space-lg">
    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="flex items-start justify-between">
        <h1 class="font-headline-md text-headline-md text-on-surface">Gradebook</h1>
        @if ($canManage)
            <div class="flex-shrink-0 flex items-center gap-space-sm">
                @if ($isLocalEnv)
                    <button
                        type="button"
                        wire:click="randomizeScores"
                        wire:loading.attr="disabled"
                        wire:target="randomizeScores"
                        class="px-space-md py-space-sm rounded-lg bg-secondary text-on-secondary font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm disabled:opacity-50"
                        title="Randomize gradebook scores (dev only)"
                    >
                        <span wire:loading.remove wire:target="randomizeScores" class="material-symbols-outlined text-[18px]">casino</span>
                        <span wire:loading wire:target="randomizeScores" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                        Randomize Scores
                    </button>
                    <button
                        type="button"
                        @click="$dispatch('open-delete-confirm', { id: '{{ $course->id }}', type: 'gradebook-scores' })"
                        wire:loading.attr="disabled"
                        wire:target="resetScores,delete-confirmed"
                        class="px-space-md py-space-sm rounded-lg bg-error text-on-error font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm disabled:opacity-50"
                        title="Reset all gradebook scores (dev only)"
                    >
                        <span wire:loading.remove wire:target="resetScores,delete-confirmed" class="material-symbols-outlined text-[18px]">restart_alt</span>
                        <span wire:loading wire:target="resetScores,delete-confirmed" class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                        Reset Scores
                    </button>
                @endif
                <a
                    href="{{ route('gradebook.weights', $course) }}"
                    wire:navigate
                    class="px-space-md py-space-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
                >
                    <span class="material-symbols-outlined text-[18px]">percent</span>
                    Change Weights
                </a>
            </div>
        @endif
    </div>

    @if ($isStudent)
        @include('livewire.courses.partials.gradebook-breakdown')
    @else
        <!-- Teacher: per-student Final Score grid -->
        <div class="flex items-center gap-space-sm">
            <label for="gradeFilter-select" class="text-body-sm text-on-surface-variant">{{ __('Grade') }}</label>
            <select
                id="gradeFilter-select"
                wire:model.live="gradeFilter"
                class="h-9 px-space-sm rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none"
            >
                <option value="">{{ __('All grades') }}</option>
                @foreach (['A', 'B', 'C', 'D', 'E'] as $grade)
                    <option value="{{ $grade }}">{{ $grade }}</option>
                @endforeach
            </select>

            @if ($gradeFilter !== '')
                <button
                    type="button"
                    wire:click="$set('gradeFilter', '')"
                    class="text-body-sm text-primary font-medium hover:underline flex-shrink-0"
                >
                    {{ __('Clear filter') }}
                </button>
            @endif
        </div>

        <x-ui.pagination-links
            :paginator="$studentRows"
            perPageModel="perPage"
            :perPageOptions="[12, 24, 48, 96]"
            searchModel="studentSearch"
            searchPlaceholder="Search students..."
            :search="$studentSearch"
        />

        <!-- Skeleton Loading (shown while paginating, searching, changing per-page, or randomizing/resetting scores) -->
        <div
            wire:loading.class.remove="hidden"
            wire:target="gotoPage,previousPage,nextPage,studentSearch,gradeFilter,perPage,randomizeScores,resetScores,delete-confirmed"
            class="hidden bg-surface border border-outline-variant rounded-lg overflow-hidden"
        >
            <x-ui.person-grid-skeleton :rows="$perPage" />
        </div>

        <div
            wire:loading.remove
            wire:target="gotoPage,previousPage,nextPage,studentSearch,gradeFilter,perPage,randomizeScores,resetScores,delete-confirmed"
            class="bg-surface border border-outline-variant rounded-lg overflow-hidden"
        >
            <x-ui.person-grid>
                @forelse ($studentRows as $row)
                    <a
                        wire:key="roster-{{ $row['user']->id }}"
                        href="{{ route('gradebook.show', [$course, $row['user']]) }}"
                        wire:navigate
                        class="bg-surface p-space-lg flex flex-col items-center text-center gap-space-sm hover:bg-surface-container/50 transition-colors"
                    >
                        <x-avatar :user="$row['user']" size="12" />
                        <p class="font-label-lg text-label-lg text-on-surface uppercase">{{ $row['user']->name }}</p>
                        <p class="font-headline-lg text-headline-lg text-primary">{{ $row['grade'] ?? '—' }}</p>
                    </a>
                @empty
                    <div class="bg-surface p-space-lg text-center text-body-sm text-on-surface-variant col-span-full">No students enrolled yet.</div>
                @endforelse
                <x-ui.person-grid-filler :count="$studentRows->count()" />
            </x-ui.person-grid>
        </div>

        <x-ui.pagination-links :paginator="$studentRows" perPageModel="perPage" :perPageOptions="[12, 24, 48, 96]" />
    @endif
</div>
