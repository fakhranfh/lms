@section('title', $course->title)

<div
    class="space-y-space-lg"
    x-data="{
        deleteId: null, deleteName: null, showDeleteModal: false,
        showBulkDeleteModal: false,
        selectedThreadIds: [],
        showOverflow: false,
        showThreadForm: false,
        pendingSessionId: null,
        async selectSession(id) {
            this.pendingSessionId = id;
            this.showThreadForm = false;
            this.selectedThreadIds = [];
            await this.$wire.selectSession(id);
            this.pendingSessionId = null;
        },
        toggleSelectAllOnPage(checked, pageIds) {
            if (checked) {
                this.selectedThreadIds = [...new Set([...this.selectedThreadIds, ...pageIds])];
            } else {
                this.selectedThreadIds = this.selectedThreadIds.filter(id => ! pageIds.includes(id));
            }
        },
        confirmBulkDelete() {
            this.showBulkDeleteModal = false;
            this.$wire.call('bulkDeleteThreads', this.selectedThreadIds);
            this.selectedThreadIds = [];
        },
    }"
    x-on:thread-created.window="showThreadForm = false"
>
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if (app()->isLocal() && $canCreate)
        <div class="px-gutter py-space-md bg-secondary/10 border border-secondary/20 rounded-lg flex items-center gap-space-md flex-wrap">
            <span class="material-symbols-outlined text-secondary text-[20px]">science</span>
            <p class="font-body-sm text-body-sm text-secondary">Dev only: generate fake threads for this session's forum.</p>
            <input
                type="number"
                min="1"
                max="50"
                wire:model="generateThreadCount"
                class="w-20 h-9 px-space-sm rounded-lg border border-outline-variant bg-surface text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
            <button
                type="button"
                wire:click="generateThreads"
                wire:loading.attr="disabled"
                wire:target="generateThreads"
                class="px-space-md py-space-xs rounded-lg border border-secondary text-secondary font-label-sm text-label-sm hover:bg-secondary/10 transition disabled:opacity-50 inline-flex items-center gap-space-xs"
            >
                <span wire:loading wire:target="generateThreads" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                Generate Threads
            </button>
        </div>
    @endif

    <!-- Session tab bar -->
    <div class="flex gap-space-xs overflow-x-auto pb-space-xs border-b border-outline-variant">
        @foreach ($sessionTabs['visible'] as $tab)
            <button
                type="button"
                @click="selectSession('{{ $tab['key'] }}')"
                wire:key="forum-tab-{{ $tab['key'] }}"
                class="relative flex-shrink-0 px-space-lg py-space-md text-left rounded-t-lg font-label-sm text-label-sm whitespace-nowrap border-b-2 transition-colors"
                :class="(pendingSessionId ? pendingSessionId === '{{ $tab['key'] }}' : {{ $tab['active'] ? 'true' : 'false' }}) ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container text-on-surface-variant border-transparent hover:bg-surface-container/70'"
            >
                @if ($tab['unreadCount'] > 0)
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-error"></span>
                @endif
                <span class="block font-label-xs text-label-xs" :class="(pendingSessionId ? pendingSessionId === '{{ $tab['key'] }}' : {{ $tab['active'] ? 'true' : 'false' }}) ? 'text-on-primary/80' : 'text-error'">
                    {{ $tab['unreadCount'] }} Unread Posts
                </span>
                <span class="block font-label-md text-label-md">{{ $tab['label'] }}</span>
            </button>
        @endforeach

        @if (! empty($sessionTabs['overflow']))
            <div class="relative flex-shrink-0" @click.outside="showOverflow = false">
                <button
                    @click="showOverflow = ! showOverflow"
                    type="button"
                    class="h-full px-space-lg py-space-md rounded-t-lg bg-surface-container hover:bg-surface-container-high font-label-md text-label-md text-on-surface-variant inline-flex items-center gap-space-xs"
                >
                    {{ count($sessionTabs['overflow']) }} more
                    <span class="material-symbols-outlined text-[18px]">expand_more</span>
                </button>

                <div x-show="showOverflow" x-cloak class="absolute right-0 z-10 mt-1 w-56 bg-surface border border-outline-variant rounded-lg shadow-lg overflow-hidden">
                    @foreach ($sessionTabs['overflow'] as $tab)
                        <button
                            @click="showOverflow = false; selectSession('{{ $tab['key'] }}')"
                            type="button"
                            class="w-full flex items-center justify-between gap-space-md px-space-md py-space-sm text-left hover:bg-surface-container transition {{ $tab['active'] ? 'bg-primary/5 text-primary' : 'text-on-surface' }}"
                        >
                            <span class="font-label-sm text-label-sm truncate">{{ $tab['label'] }}</span>
                            @if ($tab['unreadCount'] > 0)
                                <span class="text-label-xs text-error flex-shrink-0">{{ $tab['unreadCount'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Skeleton loading while switching session tabs -->
    <div wire:loading wire:target="selectSession" class="w-full space-y-space-lg animate-pulse">
        <!-- Header info skeleton -->
        <div class="w-full space-y-space-sm">
            <div class="h-6 bg-surface-container rounded w-1/3"></div>
            <div class="flex flex-wrap gap-space-xl">
                <div class="space-y-space-xs">
                    <div class="h-3 bg-surface-container rounded w-10"></div>
                    <div class="h-4 bg-surface-container rounded w-32"></div>
                </div>
                <div class="space-y-space-xs">
                    <div class="h-3 bg-surface-container rounded w-10"></div>
                    <div class="h-4 bg-surface-container rounded w-32"></div>
                </div>
                <div class="space-y-space-xs">
                    <div class="h-3 bg-surface-container rounded w-16"></div>
                    <div class="h-4 bg-surface-container rounded w-12"></div>
                </div>
            </div>
        </div>

        <!-- Create button skeleton -->
        <div class="h-10 w-44 bg-surface-container rounded-lg"></div>

        <!-- Result count + pagination controls skeleton -->
        <div class="w-full flex flex-wrap items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant">
            <div class="h-4 bg-surface-container rounded w-20"></div>
            <div class="h-9 w-40 bg-surface-container rounded-lg"></div>
        </div>

        <!-- Thread list skeleton -->
        <div class="w-full bg-surface border border-outline-variant rounded-lg overflow-hidden">
            @for ($i = 0; $i < 4; $i++)
                <div class="p-space-lg border-b border-outline-variant last:border-0 flex items-start gap-space-md">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex-shrink-0"></div>
                    <div class="min-w-0 flex-1 space-y-space-xs">
                        <div class="h-3 bg-surface-container rounded w-1/4"></div>
                        <div class="h-3 bg-surface-container rounded w-1/3"></div>
                        <div class="h-4 bg-surface-container rounded w-2/3"></div>
                    </div>
                    <div class="flex items-center gap-space-md flex-shrink-0">
                        <div class="h-4 w-8 bg-surface-container rounded"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <div wire:loading.remove wire:target="selectSession" class="w-full space-y-space-lg">
        <!-- Header info -->
        <div class="space-y-space-sm">
            <div class="flex items-center justify-between gap-space-md">
                <h1 class="font-headline-md text-headline-md text-on-surface">{{ $activeTitle }}</h1>

                @if ($canModerate)
                    <a
                        href="{{ route('forum.monitoring.index', [$course, 'session' => $activeSession->id]) }}"
                        wire:navigate
                        class="flex-shrink-0 px-space-md py-space-xs rounded-lg border border-outline text-on-surface font-label-sm text-label-sm hover:bg-surface-container transition-colors inline-flex items-center gap-space-xs"
                    >
                        <span class="material-symbols-outlined text-[18px]">monitoring</span>
                        Monitor Students
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap gap-space-xl">
                <div>
                    <p class="text-body-xs text-on-surface-variant">Start</p>
                    <p class="text-body-sm text-on-surface">{{ $activeSession->date_start_display->format('d M Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-body-xs text-on-surface-variant">End</p>
                    <p class="text-body-sm text-on-surface">{{ $activeSession->date_end_display->format('d M Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-body-xs text-on-surface-variant">Total Post</p>
                    <p class="text-body-sm text-on-surface">{{ $totalThreads + $totalComments }}</p>
                </div>
            </div>
        </div>

        @if ($canCreate)
            <button
                @click="showThreadForm = true"
                x-show="! showThreadForm"
                type="button"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
            >
                Create New Thread
            </button>

            <!-- Create thread form -->
            <div x-show="showThreadForm" x-cloak class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Title</label>
                    <input
                        type="text"
                        wire:model="newThreadTitle"
                        class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                    />
                    @error('newThreadTitle') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                    <x-rich-text-editor id="new-thread" wire-model="newThreadDescription" :value="$newThreadDescription" :disabled="! $forumWindowOpen" />
                    @error('newThreadDescription') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                @if (! $forumWindowOpen)
                    <p class="text-body-xs text-on-surface-variant">Posting is only available during the session's scheduled dates.</p>
                @endif

                <div class="flex gap-space-md">
                    <button
                        @click="showThreadForm = false"
                        type="button"
                        class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="createThread"
                        wire:loading.attr="disabled"
                        wire:target="createThread"
                        type="button"
                        @disabled(! $forumWindowOpen)
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="createThread">Post Thread</span>
                        <span wire:loading wire:target="createThread">Posting…</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Result count + pagination controls -->
        <div class="flex flex-wrap items-center justify-between gap-space-md pb-space-sm border-b border-outline-variant">
            <p class="text-body-sm text-on-surface-variant">{{ $pagination['total'] }} Result{{ $pagination['total'] !== 1 ? 's' : '' }}</p>

            <div class="flex items-center gap-space-lg">
                <label class="flex items-center gap-space-sm">
                    <span class="text-body-sm text-on-surface-variant">Show:</span>
                    <select wire:model.live="perPage" class="h-[36px] px-2 rounded-lg border border-outline-variant bg-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </label>

                @if ($pagination['lastPage'] > 1)
                    <div class="flex items-center gap-space-xs">
                        <button wire:click="gotoPage(1)" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled($pagination['onFirstPage'])>«</button>
                        <button wire:click="gotoPage({{ $pagination['currentPage'] - 1 }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled($pagination['onFirstPage'])>‹</button>
                        <button wire:click="gotoPage({{ $pagination['currentPage'] + 1 }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled(! $pagination['hasMorePages'])>›</button>
                        <button wire:click="gotoPage({{ $pagination['lastPage'] }})" type="button" class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container disabled:opacity-40" @disabled(! $pagination['hasMorePages'])>»</button>
                    </div>

                    <label class="flex items-center gap-space-sm">
                        <span class="text-body-sm text-on-surface-variant">Page:</span>
                        <select wire:model.live="page" class="h-[36px] px-2 rounded-lg border border-outline-variant bg-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                            @for ($i = 1; $i <= $pagination['lastPage']; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </label>
                @endif
            </div>
        </div>

        <!-- Thread list skeleton while changing per-page or page -->
        <div wire:loading wire:target="perPage, gotoPage, page" class="w-full bg-surface border border-outline-variant rounded-lg overflow-hidden animate-pulse">
            @for ($i = 0; $i < 4; $i++)
                <div class="w-full p-space-lg border-b border-outline-variant last:border-0 flex items-start gap-space-md">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex-shrink-0"></div>
                    <div class="min-w-0 flex-1 space-y-space-xs">
                        <div class="h-3 bg-surface-container rounded w-1/4"></div>
                        <div class="h-3 bg-surface-container rounded w-1/3"></div>
                        <div class="h-4 bg-surface-container rounded w-full"></div>
                    </div>
                    <div class="flex items-center gap-space-md flex-shrink-0">
                        <div class="h-4 w-8 bg-surface-container rounded"></div>
                    </div>
                </div>
            @endfor
        </div>

        <!-- Thread list -->
        <div wire:loading.remove wire:target="perPage, gotoPage, page">
            @if (empty($threadRows))
                <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
                    <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">forum</span>
                    <p class="text-body-md text-on-surface-variant mb-4">No threads yet.</p>
                    @if ($canCreate)
                        <button @click="showThreadForm = true" type="button" class="text-primary font-medium hover:underline">
                            Start First Thread
                        </button>
                    @endif
                </div>
            @else
                @if ($canModerate)
                    <div class="flex items-center justify-between gap-space-md mb-space-sm flex-wrap">
                        <label class="inline-flex items-center gap-space-xs text-body-sm text-on-surface-variant cursor-pointer">
                            <input
                                type="checkbox"
                                @change="toggleSelectAllOnPage($event.target.checked, @js($currentPageThreadIds))"
                                :checked="@js($currentPageThreadIds).length > 0 && @js($currentPageThreadIds).every(id => selectedThreadIds.includes(id))"
                                class="w-4 h-4 accent-primary"
                            />
                            Select all on this page
                        </label>

                        <div class="flex items-center gap-space-sm" x-show="selectedThreadIds.length > 0" x-cloak>
                            <span class="text-body-sm text-on-surface-variant" x-text="selectedThreadIds.length + ' selected'"></span>
                            <button
                                type="button"
                                @click="showBulkDeleteModal = true"
                                class="px-space-md py-space-xs rounded-lg border border-error text-error font-label-sm text-label-sm hover:bg-error/10 transition inline-flex items-center gap-space-xs"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                Delete Selected
                            </button>
                        </div>
                    </div>
                @endif

                <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                    @foreach ($threadRows as $row)
                        <div wire:key="thread-{{ $row['id'] }}" class="p-space-lg border-b border-outline-variant last:border-0">
                            <div wire:loading wire:target="confirmDeleteThread('{{ $row['id'] }}'), bulkDeleteThreads" class="w-full flex items-start gap-space-md animate-pulse">
                                <div class="w-10 h-10 rounded-full bg-surface-container flex-shrink-0"></div>
                                <div class="min-w-0 flex-1 space-y-space-xs">
                                    <div class="h-3 bg-surface-container rounded w-1/4"></div>
                                    <div class="h-3 bg-surface-container rounded w-1/3"></div>
                                    <div class="h-4 bg-surface-container rounded w-full"></div>
                                </div>
                                <div class="h-4 w-8 bg-surface-container rounded flex-shrink-0"></div>
                            </div>
                            <div wire:loading.remove wire:target="confirmDeleteThread('{{ $row['id'] }}'), bulkDeleteThreads" class="flex items-start gap-space-md">
                            @if ($canModerate)
                                <input
                                    type="checkbox"
                                    value="{{ $row['id'] }}"
                                    x-model="selectedThreadIds"
                                    class="w-4 h-4 mt-space-sm accent-primary flex-shrink-0"
                                />
                            @endif
                            <div class="relative flex-shrink-0">
                                @if ($row['isUnread'])
                                    <span class="absolute -top-0.5 -left-0.5 w-2.5 h-2.5 rounded-full bg-error border-2 border-surface z-10"></span>
                                @endif
                                @if ($row['userAvatarUrl'])
                                    <img src="{{ $row['userAvatarUrl'] }}" alt="{{ $row['userName'] }}" class="w-10 h-10 rounded-full object-cover border border-outline-variant">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-on-primary font-label-md text-label-md">
                                        {{ $row['userInitial'] }}
                                    </div>
                                @endif
                            </div>

                            <a href="{{ route('forum.thread.show', [$course, $row['id']]) }}" wire:navigate class="min-w-0 flex-1">
                                <p class="text-body-sm text-on-surface">
                                    <span class="font-medium">{{ $row['userName'] }}</span>
                                    @if ($row['roleLabel'])
                                        <span class="text-on-surface-variant">&middot;</span>
                                        <span class="text-primary">{{ $row['roleLabel'] }}</span>
                                    @endif
                                </p>
                                <p class="text-body-xs text-on-surface-variant">{{ $row['createdAtLabel'] }}</p>
                                <h3 class="font-body-md text-body-md text-on-surface mt-1 {{ $row['isUnread'] ? 'font-semibold' : 'font-normal' }}">{{ $row['title'] }}</h3>
                            </a>

                            <div class="flex items-center gap-space-md flex-shrink-0">
                                <span class="inline-flex items-center gap-1 text-body-sm text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[18px]">chat_bubble_outline</span>
                                    {{ $row['commentsCount'] }}
                                </span>

                                @if ($row['canDelete'])
                                    <button
                                        @click="deleteId = @js($row['id']); deleteName = @js($row['title']); showDeleteModal = true"
                                        type="button"
                                        class="p-space-sm text-on-surface-variant hover:text-error transition"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showDeleteModal = false"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        ></div>

        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Delete Confirmation</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            Are you sure you want to delete "<span class="font-medium" x-text="deleteName ?? 'this thread'"></span>"?
                            This action cannot be undone.
                        </p>
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
                            @click="showDeleteModal = false; $wire.call('confirmDeleteThread', deleteId)"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showBulkDeleteModal = false"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        ></div>

        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Delete Confirmation</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            Are you sure you want to delete <span class="font-medium" x-text="selectedThreadIds.length"></span> selected thread<span x-text="selectedThreadIds.length === 1 ? '' : 's'"></span>?
                            This action cannot be undone.
                        </p>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            @click="showBulkDeleteModal = false"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmBulkDelete()"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
