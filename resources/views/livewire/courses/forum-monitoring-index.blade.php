@section('title', $course->title)

<div
    class="space-y-space-lg"
    x-data="{
        showDeleteAllPostsModal: false,
        viewingUser: null,
        viewingThreads: [],
        viewingComments: [],
        viewingLoading: false,
        viewPosts(user, threads, comments) {
            this.viewingUser = user;
            this.viewingLoading = true;
            this.viewingThreads = [];
            this.viewingComments = [];
            setTimeout(() => {
                this.viewingThreads = threads;
                this.viewingComments = comments;
                this.viewingLoading = false;
            }, 300);
        },
        closePosts() {
            this.viewingUser = null;
            this.viewingThreads = [];
            this.viewingComments = [];
            this.viewingLoading = false;
        },
        pendingDeleteType: null,
        pendingDeleteId: null,
        pendingDeleteLabel: '',
        confirmDeleteThread(threadId, title) {
            this.pendingDeleteType = 'thread';
            this.pendingDeleteId = threadId;
            this.pendingDeleteLabel = title;
        },
        confirmDeleteComment(commentId) {
            this.pendingDeleteType = 'comment';
            this.pendingDeleteId = commentId;
            this.pendingDeleteLabel = 'this comment';
        },
        cancelPendingDelete() {
            this.pendingDeleteType = null;
            this.pendingDeleteId = null;
            this.pendingDeleteLabel = '';
        },
        executePendingDelete() {
            if (this.pendingDeleteType === 'thread') {
                this.viewingThreads = this.viewingThreads.filter(t => t.id !== this.pendingDeleteId);
                $wire.call('deleteStudentThread', this.pendingDeleteId);
            } else if (this.pendingDeleteType === 'comment') {
                this.viewingComments = this.viewingComments.filter(c => c.id !== this.pendingDeleteId);
                $wire.call('deleteStudentComment', this.pendingDeleteId);
            }
            this.cancelPendingDelete();
        },
    }"
>
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="flex items-start justify-between gap-space-md flex-wrap">
        <h1 class="font-headline-md text-headline-md text-on-surface">Forum Monitoring</h1>

        <a
            href="{{ route('forum.index', $selectedSession ? [$course, 'session' => $selectedSession->id] : [$course]) }}"
            wire:navigate
            class="flex-shrink-0 px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors inline-flex items-center gap-space-xs"
        >
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to Forum
        </a>
    </div>

    @if ($selectedSession)
        <div>
            <p class="font-headline-sm text-headline-sm text-on-surface">{{ $selectedSession->title }}</p>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-space-xs">
                Minimum {{ $required }} post{{ $required === 1 ? '' : 's' }} required for this session.
            </p>
        </div>

        <x-ui.pagination-links
            :paginator="$studentRows"
            perPageModel="perPage"
            :perPageOptions="[10, 25, 50, 100]"
            searchModel="studentSearch"
            searchPlaceholder="Search students..."
            :search="$studentSearch"
        />

        @if (app()->isLocal())
            <div class="px-gutter py-space-md bg-secondary/10 border border-secondary/20 rounded-lg flex items-center gap-space-md flex-wrap">
                <span class="material-symbols-outlined text-secondary text-[20px]">science</span>
                <p class="font-body-sm text-body-sm text-secondary">Dev only: autofill 2 comments per student for this session.</p>
                <button
                    type="button"
                    wire:click="autofillComments"
                    wire:loading.attr="disabled"
                    wire:target="autofillComments"
                    class="px-space-md py-space-xs rounded-lg border border-secondary text-secondary font-label-sm text-label-sm hover:bg-secondary/10 transition disabled:opacity-50 inline-flex items-center gap-space-xs"
                >
                    <span wire:loading wire:target="autofillComments" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                    Autofill 2 Comments per Student
                </button>
                <button
                    type="button"
                    @click="showDeleteAllPostsModal = true"
                    wire:loading.attr="disabled"
                    wire:target="deleteAllPosts"
                    class="px-space-md py-space-xs rounded-lg border border-error text-error font-label-sm text-label-sm hover:bg-error/10 transition disabled:opacity-50 inline-flex items-center gap-space-xs"
                >
                    <span wire:loading wire:target="deleteAllPosts" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                    Delete All Posts for All Students
                </button>
            </div>
        @endif

        <!-- Skeleton Loading -->
        <div
            wire:loading.class.remove="hidden"
            wire:target="gotoPage,previousPage,nextPage,studentSearch,perPage,autofillComments,deleteAllPosts"
            class="hidden bg-surface border border-outline-variant rounded-lg overflow-hidden animate-pulse"
        >
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container/50">
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Student</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Threads</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Comments</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Total Posts</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Requirement</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="px-space-lg py-space-md">
                                    <div class="flex items-center gap-space-sm">
                                        <x-ui.skeleton-box class="h-8 w-8 rounded-full flex-shrink-0" />
                                        <x-ui.skeleton-box class="h-4 w-32" />
                                    </div>
                                </td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-4 w-8" /></td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-4 w-8" /></td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-4 w-8" /></td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-6 w-24 rounded-full" /></td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-4 w-16" /></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        <div wire:loading.remove wire:target="gotoPage,previousPage,nextPage,studentSearch,perPage,autofillComments,deleteAllPosts" class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container/50">
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Student</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Threads</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Comments</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Total Posts</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Requirement</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($studentRows as $row)
                            <tr wire:key="forum-monitoring-student-{{ $row['user']->id }}">
                                <td class="px-space-lg py-space-md">
                                    <div class="flex items-center gap-space-sm">
                                        <x-avatar :user="$row['user']" size="8" />
                                        <span class="font-label-md text-label-md text-on-surface">{{ $row['user']->name }}</span>
                                    </div>
                                </td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['threadCount'] }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['commentCount'] }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['totalPosts'] }}</td>
                                <td class="px-space-lg py-space-md">
                                    @if ($row['met'])
                                        <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-success/10 text-success">
                                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                            Met
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-error/10 text-error">
                                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                                            {{ $row['remaining'] }} post{{ $row['remaining'] === 1 ? '' : 's' }} remaining
                                        </span>
                                    @endif
                                </td>
                                <td class="px-space-lg py-space-md">
                                    <button
                                        type="button"
                                        @click="viewPosts(@js(['id' => $row['user']->id, 'name' => $row['user']->name]), @js($row['threadsJson']), @js($row['commentsJson']))"
                                        class="text-body-sm text-primary font-medium hover:underline inline-flex items-center gap-space-xs"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        View Posts
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No students found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-ui.pagination-links :paginator="$studentRows" perPageModel="perPage" :perPageOptions="[10, 25, 50, 100]" />
    @else
        <p class="text-body-sm text-on-surface-variant py-space-md">No online sessions yet.</p>
    @endif

    <!-- Student Posts Modal -->
    <div x-show="viewingUser" x-cloak class="fixed inset-0 z-50">
        <div @click="closePosts()" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-lg w-full max-h-[80vh] flex flex-col">
                <div class="p-space-lg border-b border-outline-variant flex items-center justify-between gap-space-md flex-shrink-0">
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface"><span x-text="viewingUser?.name"></span>'s Posts</h3>
                        <p class="text-body-xs text-on-surface-variant">{{ $selectedSession?->title }}</p>
                    </div>
                    <button type="button" @click="closePosts()" class="text-on-surface-variant hover:text-on-surface flex-shrink-0">
                        <span class="material-symbols-outlined text-[22px]">close</span>
                    </button>
                </div>

                <div class="p-space-lg space-y-space-lg overflow-y-auto">
                    <!-- Skeleton Loading -->
                    <div x-show="viewingLoading" x-cloak class="space-y-space-lg animate-pulse">
                        <div>
                            <x-ui.skeleton-box class="h-4 w-24 mb-space-sm" />
                            @for ($i = 0; $i < 2; $i++)
                                <div class="flex items-start justify-between gap-space-md py-space-sm border-b border-outline-variant last:border-0">
                                    <div class="min-w-0 flex-1 space-y-space-xs">
                                        <x-ui.skeleton-box class="h-4 w-2/3" />
                                        <x-ui.skeleton-box class="h-3 w-1/3" />
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <div>
                            <x-ui.skeleton-box class="h-4 w-28 mb-space-sm" />
                            @for ($i = 0; $i < 2; $i++)
                                <div class="flex items-start justify-between gap-space-md py-space-sm border-b border-outline-variant last:border-0">
                                    <div class="min-w-0 flex-1 space-y-space-xs">
                                        <x-ui.skeleton-box class="h-4 w-full" />
                                        <x-ui.skeleton-box class="h-3 w-1/2" />
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- Loaded content -->
                    <div x-show="! viewingLoading" x-cloak class="space-y-space-lg">
                        <div>
                            <p class="font-label-md text-label-md text-on-surface-variant mb-space-sm">Threads (<span x-text="viewingThreads.length"></span>)</p>

                            <template x-for="thread in viewingThreads" :key="thread.id">
                                <div class="flex items-start justify-between gap-space-md py-space-sm border-b border-outline-variant last:border-0">
                                    <a
                                        :href="'{{ route('forum.thread.show', [$course, '__ID__']) }}'.replace('__ID__', thread.id)"
                                        class="min-w-0 hover:underline"
                                    >
                                        <p class="font-label-md text-label-md text-on-surface truncate" x-text="thread.title"></p>
                                        <p class="text-body-xs text-on-surface-variant" x-text="thread.createdAt"></p>
                                    </a>
                                    <button
                                        type="button"
                                        @click="confirmDeleteThread(thread.id, thread.title)"
                                        class="p-space-xs text-on-surface-variant hover:text-error transition flex-shrink-0"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </template>

                            <p class="text-body-sm text-on-surface-variant" x-show="viewingThreads.length === 0">No threads.</p>
                        </div>

                        <div>
                            <p class="font-label-md text-label-md text-on-surface-variant mb-space-sm">Comments (<span x-text="viewingComments.length"></span>)</p>

                            <template x-for="comment in viewingComments" :key="comment.id">
                                <div class="flex items-start justify-between gap-space-md py-space-sm border-b border-outline-variant last:border-0">
                                    <a
                                        :href="'{{ route('forum.thread.show', [$course, '__ID__']) }}'.replace('__ID__', comment.threadId) + '?comment=' + comment.id + '#comment-' + comment.id"
                                        class="min-w-0 hover:underline"
                                    >
                                        <p class="text-body-sm text-on-surface line-clamp-2" x-text="comment.body"></p>
                                        <p class="text-body-xs text-on-surface-variant mt-1">
                                            on "<span x-text="comment.threadTitle"></span>" &middot; <span x-text="comment.createdAt"></span>
                                        </p>
                                    </a>
                                    <button
                                        type="button"
                                        @click="confirmDeleteComment(comment.id)"
                                        class="p-space-xs text-on-surface-variant hover:text-error transition flex-shrink-0"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </template>

                            <p class="text-body-sm text-on-surface-variant" x-show="viewingComments.length === 0">No comments.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Post Confirmation Modal -->
    <div x-show="pendingDeleteType" x-cloak class="fixed inset-0 z-50">
        <div
            @click="cancelPendingDelete()"
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
                            Are you sure you want to delete "<span class="font-medium" x-text="pendingDeleteLabel"></span>"?
                            This action cannot be undone.
                        </p>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            type="button"
                            @click="cancelPendingDelete()"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="executePendingDelete()"
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete All Posts Confirmation Modal -->
    <div x-show="showDeleteAllPostsModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showDeleteAllPostsModal = false"
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
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Delete All Posts?</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            This deletes every thread and comment in this session's forum for all students. This cannot be undone.
                        </p>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            type="button"
                            @click="showDeleteAllPostsModal = false"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="showDeleteAllPostsModal = false; $wire.call('deleteAllPosts')"
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
