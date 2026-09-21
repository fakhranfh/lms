@section('title', 'Raport')

<div class="space-y-space-lg">
    <div class="flex items-start justify-between gap-space-md">
        <h1 class="font-headline-md text-headline-md text-on-surface">Raport</h1>

        @if ($isStudent)
            <a
                href="{{ route('raport.export.self') }}"
                class="flex-shrink-0 px-space-md py-space-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
            >
                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                Export PDF
            </a>
        @endif
    </div>

    @if ($isStudent)
        @if ($courseCards->isEmpty())
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center text-body-sm text-on-surface-variant">
                You are not enrolled in any course yet.
            </div>
        @else
            <div class="space-y-space-xl">
                @foreach ($courseCards as $card)
                    @include('livewire.raport.partials.raport-course-card', ['card' => $card])
                @endforeach
            </div>
        @endif
    @else
        <div class="flex items-center gap-space-md flex-wrap">
            <div
                wire:ignore
                class="flex items-center gap-space-sm flex-wrap"
                x-data="{
                    selected: @js($gradeFilter),
                    timer: null,
                    toggle(grade) {
                        this.selected = this.selected.includes(grade)
                            ? this.selected.filter(g => g !== grade)
                            : [...this.selected, grade];

                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => $wire.applyGradeFilter(this.selected), 500);
                    },
                    clear() {
                        this.selected = [];
                        clearTimeout(this.timer);
                        $wire.applyGradeFilter([]);
                    },
                }"
            >
                <span class="text-body-sm text-on-surface-variant">{{ __('Grade') }}</span>

                <div class="flex items-center gap-space-xs flex-wrap">
                    @foreach (['A', 'B', 'C', 'D', 'E'] as $grade)
                        <button
                            type="button"
                            x-on:click="toggle('{{ $grade }}')"
                            x-bind:aria-pressed="selected.includes('{{ $grade }}').toString()"
                            x-bind:class="selected.includes('{{ $grade }}') ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-lowest text-on-surface-variant border-outline-variant hover:border-primary'"
                            class="px-space-sm py-1 rounded-full font-label-sm text-label-sm border transition-colors"
                        >
                            {{ $grade }}
                        </button>
                    @endforeach
                </div>

                <button
                    type="button"
                    x-show="selected.length > 0"
                    x-on:click="clear()"
                    class="text-body-sm text-primary font-medium hover:underline flex-shrink-0"
                >
                    {{ __('Clear filter') }}
                </button>
            </div>
        </div>

        <x-ui.pagination-links
            :paginator="$studentRows"
            perPageModel="perPage"
            :perPageOptions="[12, 24, 48, 96]"
            searchModel="studentSearch"
            searchPlaceholder="Search students..."
            :search="$studentSearch"
        />

        <!-- Skeleton Loading (shown while paginating, searching, changing per-page/grade filters) -->
        <div
            wire:loading.class.remove="hidden"
            wire:target="gotoPage,previousPage,nextPage,studentSearch,applyGradeFilter,perPage"
            class="hidden bg-surface border border-outline-variant rounded-lg overflow-hidden"
        >
            <x-ui.person-grid-skeleton :rows="$perPage" />
        </div>

        <div
            wire:loading.remove
            wire:target="gotoPage,previousPage,nextPage,studentSearch,applyGradeFilter,perPage"
            class="bg-surface border border-outline-variant rounded-lg overflow-hidden"
        >
            <x-ui.person-grid>
                @forelse ($studentRows as $row)
                    <a
                        wire:key="raport-roster-{{ $row['user']->id }}"
                        href="{{ route('raport.show', $row['user']) }}"
                        wire:navigate
                        class="bg-surface p-space-lg flex flex-col items-center text-center gap-space-sm hover:bg-surface-container/50 transition-colors"
                    >
                        <x-avatar :user="$row['user']" size="12" />
                        <p class="font-label-lg text-label-lg text-on-surface uppercase">{{ $row['user']->name }}</p>
                        <p class="font-headline-lg text-headline-lg text-primary">{{ $row['grade'] ?? '—' }}</p>
                    </a>
                @empty
                    <div class="bg-surface p-space-lg text-center text-body-sm text-on-surface-variant col-span-full">No students found.</div>
                @endforelse
                <x-ui.person-grid-filler :count="$studentRows->count()" />
            </x-ui.person-grid>
        </div>

        <x-ui.pagination-links :paginator="$studentRows" perPageModel="perPage" :perPageOptions="[12, 24, 48, 96]" />
    @endif
</div>
