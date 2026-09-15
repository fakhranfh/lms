@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    @error('generateCount')
        <p class="font-body-sm text-body-sm text-error">{{ $message }}</p>
    @enderror

    <!-- Header -->
    <div class="relative sticky top-0 z-20 bg-background flex items-start justify-between pt-space-xxs pb-space-sm border-b border-outline-variant before:content-[''] before:absolute before:left-0 before:right-0 before:-top-space-lg before:h-space-lg before:bg-background before:-z-10">
        <div>
            <h1 class="font-headline-md text-headline-md text-on-surface">Assessment</h1>
        </div>

        @unless ($isStudent)
            <div class="flex items-center gap-space-sm">
                <a href="{{ route('assessments.create', [$course, 'personal']) }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm">
                    <span class="material-symbols-outlined">add</span>
                    Personal Assignment
                </a>
                <a href="{{ route('assessments.create', [$course, 'team']) }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm">
                    <span class="material-symbols-outlined">add</span>
                    Team Assignment
                </a>
                <a href="{{ route('assessments.quiz.create', $course) }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm">
                    <span class="material-symbols-outlined">add</span>
                    Quiz
                </a>
                <a href="{{ route('assessments.final-exam.create', $course) }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm">
                    <span class="material-symbols-outlined">add</span>
                    Final Exam
                </a>
            </div>
        @endunless
    </div>

    <!-- Grouped Collapsible Tables -->
    <div
        class="space-y-space-lg"
        x-data="{
            deleteId: null, deleteMode: 'single', showDeleteModal: false, selectedIds: [], deletingIds: [], bulkDeleting: false,
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
        }"
    >
        @foreach ($groupedAssessments as $index => $group)
            <div x-data="{ open: true }">
                <!-- Collapsible Header -->
                <button
                    type="button"
                    wire:click="toggleSection('{{ $group['sectionKey'] }}')"
                    @click="open = !open"
                    class="w-full flex items-center justify-between px-space-lg py-space-md bg-surface border border-outline-variant rounded-lg hover:bg-surface-container/50 transition"
                >
                    <div class="flex items-center gap-space-md flex-1">
                        <span class="material-symbols-outlined text-on-surface-variant transition-transform" :class="open ? 'rotate-90' : ''">
                            chevron_right
                        </span>
                        <h2 class="font-label-lg text-label-lg text-on-surface font-bold">
                            {{ strtoupper(\App\Support\AssessmentTypeLabel::forType($group['type'])) }}: {{ rtrim(rtrim(number_format($group['totalWeight'], 2), '0'), '.') }}%
                        </h2>
                    </div>
                </button>

                <!-- Collapsible Content: Table -->
                    <div x-show="open" x-cloak>
                        @if (app()->isLocal() && ! $isStudent && $group['generateMethod'])
                            <div class="bg-tertiary-container border border-outline-variant p-space-md flex items-center justify-between gap-space-md">
                                <p class="font-body-sm text-body-sm text-on-tertiary-container">Dev tools</p>

                                @if ($group['type'] === \App\Enums\AssessmentType::TheoryFinalExam)
                                    <div class="flex items-center gap-space-sm">
                                        <input
                                            type="number"
                                            wire:model="generateCount"
                                            min="1"
                                            max="50"
                                            class="w-20 px-space-sm py-space-xs border border-outline rounded-lg font-body-sm text-body-sm"
                                        />
                                        @foreach (\App\Enums\FinalExamType::cases() as $examTypeOption)
                                            <button
                                                type="button"
                                                wire:click="generateFinalExam('{{ $examTypeOption->value }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="generateFinalExam('{{ $examTypeOption->value }}')"
                                                class="px-space-md py-space-xs bg-tertiary text-on-tertiary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-xs"
                                            >
                                                <span wire:loading wire:target="generateFinalExam('{{ $examTypeOption->value }}')" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                                                Generate {{ str($examTypeOption->value)->replace('_', ' ')->title() }}
                                            </button>
                                        @endforeach
                                        <button
                                            type="button"
                                            wire:click="generateAllFinalExamTypes"
                                            wire:loading.attr="disabled"
                                            wire:target="generateAllFinalExamTypes"
                                            class="px-space-md py-space-xs bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-xs"
                                        >
                                            <span wire:loading wire:target="generateAllFinalExamTypes" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                                            Generate All
                                        </button>
                                    </div>
                                @else
                                    <form wire:submit="{{ $group['generateMethod'] }}" class="flex items-center gap-space-sm">
                                        <input
                                            type="number"
                                            wire:model="generateCount"
                                            min="1"
                                            max="50"
                                            class="w-20 px-space-sm py-space-xs border border-outline rounded-lg font-body-sm text-body-sm"
                                        />
                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            wire:target="{{ $group['generateMethod'] }}"
                                            class="px-space-md py-space-xs bg-tertiary text-on-tertiary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-xs"
                                        >
                                            <span wire:loading wire:target="{{ $group['generateMethod'] }}" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                                            Generate {{ \App\Support\AssessmentTypeLabel::forType($group['type']) }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if ($group['type'] === \App\Enums\AssessmentType::Attendance)
                            <div class="bg-surface border border-t-0 border-outline-variant rounded-b-lg overflow-hidden">
                                @if ($isStudent)
                                    <x-assessments.session-table :rows="$group['sessionTableRows']" :empty-message="$group['sessionTableEmptyMessage']" />
                                @else
                                    <x-assessments.attendance-summary-table :rows="$group['sessionTableRows']" :empty-message="$group['sessionTableEmptyMessage']" />
                                @endif
                            </div>
                        @elseif ($group['type'] === \App\Enums\AssessmentType::ForumDiscussion)
                            <div class="bg-surface border border-t-0 border-outline-variant rounded-b-lg overflow-hidden">
                                @if ($isStudent)
                                    <x-assessments.session-table :rows="$group['sessionTableRows']" :empty-message="$group['sessionTableEmptyMessage']" />
                                @else
                                    <x-assessments.forum-discussion-summary-table :rows="$group['sessionTableRows']" :empty-message="$group['sessionTableEmptyMessage']" />
                                @endif
                            </div>
                        @elseif ($group['assessments']->isNotEmpty())
                            @unless ($isStudent)
                                <x-assessments.bulk-delete-bar />
                            @endunless
                            <div class="bg-surface border border-t-0 border-outline-variant rounded-b-lg overflow-hidden">
                                <x-assessments.table :group="$group" :is-student="$isStudent" :course="$course" />
                            </div>
                        @else
                            <div class="bg-surface border border-t-0 border-outline-variant rounded-b-lg p-space-lg text-center text-body-sm text-on-surface-variant">
                                No {{ strtolower(\App\Support\AssessmentTypeLabel::forType($group['type'])) }} yet.
                            </div>
                        @endif
                    </div>
            </div>
        @endforeach

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
                            <p class="font-body-sm text-body-sm text-on-surface-variant" x-show="deleteMode === 'single'">
                                Are you sure you want to delete this assessment? This action cannot be undone.
                            </p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant" x-show="deleteMode === 'bulk'">
                                Are you sure you want to delete <span x-text="selectedIds.length"></span> selected assessment(s)? This action cannot be undone.
                            </p>
                        </div>

                        <div class="flex gap-space-md pt-space-md">
                            <button
                                @click="showDeleteModal = false"
                                type="button"
                                :disabled="bulkDeleting"
                                class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Cancel
                            </button>
                            <button
                                @click="
                                    showDeleteModal = false;
                                    if (deleteMode === 'bulk') {
                                        bulkDeleting = true;
                                        deletingIds = [...selectedIds];
                                        $wire.call('deleteSelected', selectedIds).then(() => { selectedIds = []; deletingIds = []; bulkDeleting = false; });
                                    } else {
                                        deletingIds = [deleteId];
                                        $wire.call('deleteAssessment', deleteId).then(() => { deletingIds = []; });
                                    }
                                "
                                type="button"
                                :disabled="bulkDeleting"
                                class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Posts Modal (read-only for students) -->
        <div x-show="viewingUser" x-cloak class="fixed inset-0 z-50">
            <div @click="closePosts()" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-lg w-full max-h-[80vh] flex flex-col">
                    <div class="p-space-lg border-b border-outline-variant flex items-center justify-between gap-space-md flex-shrink-0">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Your Posts</h3>
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
                                    <a
                                        :href="'{{ route('forum.thread.show', [$course, '__ID__']) }}'.replace('__ID__', thread.id)"
                                        class="block py-space-sm border-b border-outline-variant last:border-0 hover:underline"
                                    >
                                        <p class="font-label-md text-label-md text-on-surface truncate" x-text="thread.title"></p>
                                        <p class="text-body-xs text-on-surface-variant" x-text="thread.createdAt"></p>
                                    </a>
                                </template>

                                <p class="text-body-sm text-on-surface-variant" x-show="viewingThreads.length === 0">No threads.</p>
                            </div>

                            <div>
                                <p class="font-label-md text-label-md text-on-surface-variant mb-space-sm">Comments (<span x-text="viewingComments.length"></span>)</p>

                                <template x-for="comment in viewingComments" :key="comment.id">
                                    <a
                                        :href="'{{ route('forum.thread.show', [$course, '__ID__']) }}'.replace('__ID__', comment.threadId) + '?comment=' + comment.id + '#comment-' + comment.id"
                                        class="block py-space-sm border-b border-outline-variant last:border-0 hover:underline"
                                    >
                                        <p class="text-body-sm text-on-surface line-clamp-2" x-text="comment.body"></p>
                                        <p class="text-body-xs text-on-surface-variant mt-1">
                                            on "<span x-text="comment.threadTitle"></span>" &middot; <span x-text="comment.createdAt"></span>
                                        </p>
                                    </a>
                                </template>

                                <p class="text-body-sm text-on-surface-variant" x-show="viewingComments.length === 0">No comments.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
