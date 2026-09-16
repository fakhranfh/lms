@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="flex items-start justify-between">
        <h1 class="font-headline-md text-headline-md text-on-surface">Gradebook</h1>
        @if ($canManage)
            <a
                href="{{ route('gradebook.weights', $course) }}"
                wire:navigate
                class="flex-shrink-0 px-space-md py-space-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
            >
                <span class="material-symbols-outlined text-[18px]">percent</span>
                Change Weights
            </a>
        @endif
    </div>

    @if ($isStudent)
        @include('livewire.courses.partials.gradebook-breakdown')
    @else
        <!-- Teacher: per-student Final Score grid -->
        <x-ui.pagination-links
            :paginator="$studentRows"
            perPageModel="perPage"
            :perPageOptions="[12, 24, 48, 96]"
            searchModel="studentSearch"
            searchPlaceholder="Search students..."
            :search="$studentSearch"
        />

        <!-- Skeleton Loading (shown while paginating, searching, or changing per-page) -->
        <div
            wire:loading.class.remove="hidden"
            wire:target="gotoPage,previousPage,nextPage,studentSearch,perPage"
            class="hidden bg-surface border border-outline-variant rounded-lg overflow-hidden"
        >
            <x-ui.person-grid-skeleton :rows="$perPage" />
        </div>

        <div
            wire:loading.remove
            wire:target="gotoPage,previousPage,nextPage,studentSearch,perPage"
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
