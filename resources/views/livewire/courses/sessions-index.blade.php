@section('title', $course->title)

<div
    class="space-y-space-lg"
    x-data="{
        deleteId: null,
        deleteName: null,
        showDeleteModal: false,
        deleteMode: 'single',
        dragId: null,
        currentOrder() {
            return Array.from(this.$refs.sessionList.children).map(el => el.dataset.row);
        },
        // The Session N label marks a position in the list, not the
        // session sitting in it — moving a row must not drag its old
        // number along, so relabel every row from the new DOM order.
        relabelRows() {
            Array.from(this.$refs.sessionList.children).forEach((row, index) => {
                const label = row.querySelector('[data-session-label]');
                if (label) label.textContent = `Session ${index + 1}`;
            });
        },
        onDrop(targetId) {
            const list = this.$refs.sessionList;
            const dragEl = this.dragId ? list.querySelector(`[data-row='${this.dragId}']`) : null;
            const targetEl = list.querySelector(`[data-row='${targetId}']`);
            this.dragId = null;
            if (! dragEl || ! targetEl || dragEl === targetEl) return;

            // Move the actual DOM node immediately so the reorder feels
            // instant; the wire:call below just persists it in the background.
            const rows = Array.from(list.children);
            rows.indexOf(dragEl) < rows.indexOf(targetEl) ? targetEl.after(dragEl) : targetEl.before(dragEl);
            this.relabelRows();

            $wire.call('reorderSessions', this.currentOrder());
        },
        moveRow(id, direction) {
            const list = this.$refs.sessionList;
            const rows = Array.from(list.children);
            const index = rows.findIndex(el => el.dataset.row === id);
            const swapWith = index + direction;
            if (index === -1 || swapWith < 0 || swapWith >= rows.length) return;

            direction === -1
                ? rows[index].parentNode.insertBefore(rows[index], rows[swapWith])
                : rows[index].parentNode.insertBefore(rows[swapWith], rows[index]);
            this.relabelRows();

            $wire.call(direction === -1 ? 'moveSessionUp' : 'moveSessionDown', id);
        },
    }"
>
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="font-headline-md text-headline-md text-on-surface">Sessions</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">{{ $sessions->count() }} session{{ $sessions->count() !== 1 ? 's' : '' }}</p>
        </div>

        @unless ($isStudent)
            <a
                href="{{ route('sessions.create', $course) }}"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
            >
                <span class="material-symbols-outlined">add</span>
                Add Session
            </a>
        @endunless
    </div>

    @if (app()->isLocal() && ! $isStudent)
        <div class="px-gutter py-space-md bg-secondary/10 border border-secondary/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-secondary text-[20px]">science</span>
            <p class="font-body-sm text-body-sm text-secondary flex-1">Dev only: generate dummy sessions for this course.</p>
            <input
                type="number"
                min="1"
                max="50"
                wire:model="generateCount"
                class="w-20 px-space-sm py-space-xs border border-outline rounded-lg font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-secondary/50"
            />
            <button
                type="button"
                wire:click="devGenerateSessions"
                wire:loading.attr="disabled"
                class="px-space-md py-space-xs rounded-lg bg-secondary text-on-secondary font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50"
            >
                Generate
            </button>
        </div>
    @endif

    @unless ($isStudent || $sessions->isEmpty())
        <div class="flex items-center justify-between">
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                <span x-text="$wire.selectedSessionIds.length"></span> selected
            </p>
            <div class="flex gap-space-sm">
                <button
                    type="button"
                    x-show="$wire.selectedSessionIds.length"
                    @click="deleteMode = 'bulk'; showDeleteModal = true"
                    wire:loading.attr="disabled"
                    wire:target="devGenerateSessions,confirmDelete,bulkDelete,deleteAll"
                    class="px-space-md py-space-xs rounded-lg border border-error text-error font-label-sm text-label-sm hover:bg-error/10 transition disabled:opacity-50"
                >
                    Delete Selected
                </button>
                <button
                    type="button"
                    @click="deleteMode = 'all'; showDeleteModal = true"
                    wire:loading.attr="disabled"
                    wire:target="devGenerateSessions,confirmDelete,bulkDelete,deleteAll"
                    class="px-space-md py-space-xs rounded-lg border border-error text-error font-label-sm text-label-sm hover:bg-error/10 transition disabled:opacity-50"
                >
                    Delete All
                </button>
            </div>
        </div>
    @endunless

    <!-- Sessions List -->
    <div wire:loading.block wire:target="devGenerateSessions,bulkDelete,deleteAll" class="w-full">
        <x-ui.skeleton-list :rows="max($sessions->count(), 3)" />
    </div>

    <div wire:loading.remove wire:target="devGenerateSessions,bulkDelete,deleteAll">
    @if ($sessions->isEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
            <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">calendar_month</span>
            <p class="text-body-md text-on-surface-variant mb-4">No sessions yet.</p>
            @unless ($isStudent)
                <a href="{{ route('sessions.create', $course) }}" class="text-primary font-medium hover:underline">
                    Add First Session
                </a>
            @endunless
        </div>
    @else
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="space-y-0" x-ref="sessionList">
                @foreach ($sessions as $sessionIndex => $session)
                    <div
                        wire:key="session-{{ $session->id }}"
                        data-row="{{ $session->id }}"
                        class="border-b border-outline-variant last:border-0"
                        x-data="{ open: @js($expandedSessions[$session->id] ?? false) }"
                        @unless ($isStudent)
                            draggable="true"
                            @dragstart="dragId = @js($session->id)"
                            @dragover.prevent
                            @drop.prevent="onDrop(@js($session->id))"
                        @endunless
                    >
                        <div wire:loading.block wire:target="confirmDelete('{{ $session->id }}')" class="w-full">
                            <x-ui.skeleton-row />
                        </div>

                        <div wire:loading.remove wire:target="confirmDelete('{{ $session->id }}')">
                        <!-- Session Header -->
                        <div class="p-space-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-space-md flex-1">
                                    @unless ($isStudent)
                                        <span class="material-symbols-outlined text-on-surface-variant cursor-grab select-none" title="Drag to reorder">drag_indicator</span>
                                        <input
                                            type="checkbox"
                                            wire:model="selectedSessionIds"
                                            value="{{ $session->id }}"
                                            class="w-4 h-4 rounded border-outline text-primary focus:ring-primary/50"
                                        />
                                    @endunless

                                    <button
                                        type="button"
                                        @click="open = !open; $wire.call('toggleSession', '{{ $session->id }}')"
                                        class="p-2 hover:bg-surface-container rounded transition"
                                    >
                                        <span class="material-symbols-outlined transition-transform" :class="open ? 'rotate-90' : ''">
                                            chevron_right
                                        </span>
                                    </button>

                                    <div class="flex-1">
                                        <p class="font-label-xs text-label-xs text-on-surface-variant" data-session-label>Session {{ $sessionIndex + 1 }}</p>
                                        <div class="flex items-center gap-space-md">
                                            <h3 class="font-label-lg text-label-lg text-on-surface">{{ $session->title }}</h3>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">
                                                {{ str($session->delivery_mode->value)->replace('_', ' ')->title() }}
                                            </span>
                                        </div>
                                        <p class="text-body-sm text-on-surface-variant mt-1">
                                            {{ $session->date_start->format('M j, Y') }} &ndash; {{ $session->date_end->format('M j, Y') }}
                                        </p>
                                    </div>
                                </div>

                                @unless ($isStudent)
                                    <div class="flex items-center gap-space-sm ml-auto">
                                        <div class="flex flex-col">
                                            <button
                                                type="button"
                                                @click="moveRow(@js($session->id), -1)"
                                                @if ($sessionIndex === 0) disabled @endif
                                                class="p-1 hover:bg-surface-container rounded transition text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed"
                                                title="Move up"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">keyboard_arrow_up</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="moveRow(@js($session->id), 1)"
                                                @if ($sessionIndex === $sessions->count() - 1) disabled @endif
                                                class="p-1 hover:bg-surface-container rounded transition text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed"
                                                title="Move down"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">keyboard_arrow_down</span>
                                            </button>
                                        </div>

                                        <a
                                            href="{{ route('sessions.edit', $session) }}"
                                            class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex"
                                            title="Edit session"
                                        >
                                            <span class="material-symbols-outlined">edit</span>
                                        </a>

                                        <button
                                            type="button"
                                            @click="deleteId = @js($session->id); deleteName = @js($session->title); deleteMode = 'single'; showDeleteModal = true"
                                            class="p-2 hover:bg-surface-container rounded transition text-error"
                                        >
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                @endunless
                            </div>
                        </div>

                        <!-- Detail (Expandable) -->
                        <div x-show="open" class="w-full bg-surface-container/50 border-t border-outline-variant p-space-lg space-y-space-lg">
                            @if ($session->learning_outcome)
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-space-xs">Learning Outcome</h4>
                                    <p class="text-body-sm text-on-surface-variant">{{ $session->learning_outcome }}</p>
                                </div>
                            @endif

                            @if ($session->subtopics->isNotEmpty())
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-space-xs">Subtopics</h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach ($session->subtopics as $subtopic)
                                            <li class="text-body-sm text-on-surface-variant">{{ $subtopic->subtopic }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($session->materials->isNotEmpty())
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-space-xs">Learning Materials</h4>
                                    <ul class="space-y-1">
                                        @foreach ($session->materials as $material)
                                            <li class="text-body-sm text-on-surface-variant flex items-center gap-space-xs">
                                                <span class="material-symbols-outlined text-[16px]">description</span>
                                                {{ $material->title }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($session->videoConferences->isNotEmpty())
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-space-xs">Video Conferences</h4>
                                    <ul class="space-y-2">
                                        @foreach ($session->videoConferences as $videoConference)
                                            <li class="flex items-center justify-between text-body-sm">
                                                <div>
                                                    <span class="text-on-surface">{{ $videoConference->title ?? 'Meeting' }}</span>
                                                    <span class="text-on-surface-variant ml-space-sm">
                                                        {{ $videoConference->scheduled_start_at->format('M j, Y g:i A') }}
                                                    </span>
                                                </div>
                                                @if ($videoConference->meeting_url)
                                                    <a href="{{ $videoConference->meeting_url }}" target="_blank" class="text-primary font-medium hover:underline">
                                                        Join
                                                    </a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! $session->learning_outcome && $session->subtopics->isEmpty() && $session->materials->isEmpty() && $session->videoConferences->isEmpty())
                                <p class="text-body-sm text-on-surface-variant">No additional details for this session.</p>
                            @endif
                        </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showDeleteModal = false"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div
            class="fixed inset-0 flex items-center justify-center p-4"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Delete Confirmation</h3>
                        <template x-if="deleteMode === 'single'">
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Are you sure you want to delete "<span class="font-medium" x-text="deleteName ?? 'this session'"></span>"?
                                This action cannot be undone.
                            </p>
                        </template>
                        <template x-if="deleteMode === 'bulk'">
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Are you sure you want to delete <span class="font-medium" x-text="$wire.selectedSessionIds.length"></span> selected session(s)?
                                This action cannot be undone.
                            </p>
                        </template>
                        <template x-if="deleteMode === 'all'">
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Are you sure you want to delete <span class="font-medium">all sessions</span> in this course?
                                This action cannot be undone.
                            </p>
                        </template>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            @click="showDeleteModal = false"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="
                                showDeleteModal = false;
                                if (deleteMode === 'single') { $wire.call('confirmDelete', deleteId); }
                                else if (deleteMode === 'bulk') { $wire.call('bulkDelete'); }
                                else { $wire.call('deleteAll'); }
                            "
                            wire:loading.attr="disabled"
                            wire:target="devGenerateSessions,confirmDelete,bulkDelete,deleteAll"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
