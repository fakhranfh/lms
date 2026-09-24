@section('title', 'Grade — '.$student->name)

<div class="space-y-space-lg" x-data="{ tab: 'grade' }">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div>
        <a href="{{ route('assessments.final-exam.show', $assessment) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Submissions
        </a>
    </div>

    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    <div x-data="{ dismissed: false }" x-effect="if ($wire.errorMessage) { dismissed = false }">
        <template x-teleport="body">
            <div
                x-show="$wire.errorMessage && ! dismissed"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[130] flex items-center justify-center bg-black/50 px-gutter"
                @click.self="dismissed = true"
            >
                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                    <div class="flex items-center gap-space-md">
                        <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ __('Unable to Save') }}</h2>
                    </div>
                    <p class="font-body-md text-body-md text-secondary" x-text="$wire.errorMessage"></p>
                    <div class="flex items-center justify-end gap-space-md">
                        <button type="button" @click="dismissed = true" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden" x-data="rteVideoPreview()" @click="onContentClick($event)">
        <div class="flex items-center gap-space-md px-space-lg py-space-md border-b border-outline-variant">
            <x-avatar :user="$student" size="10" />
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Grade &mdash; {{ $student->name }}</h2>
                @if ($attempt)
                    <p class="text-body-sm text-on-surface-variant">
                        Attempt {{ $attempt->attempt_number }} &middot; submitted {{ $attempt->submitted_at_display?->format('M j, Y H:i') }}
                    </p>
                @endif
            </div>
        </div>

        @if ($finalScore)
            <div class="mx-space-lg mt-space-lg mb-space-lg p-space-md bg-primary/10 border border-primary/20 rounded-lg flex items-center justify-between">
                <div>
                    <p class="font-label-md text-label-md text-on-surface">{{ __('Final Score') }}</p>
                    <p class="text-body-sm text-on-surface-variant">
                        {{ __('Graded') }} {{ $finalScore->graded_at_display?->format('M j, Y H:i') }}
                        @if ($finalScore->feedback)
                            &middot; {{ $finalScore->feedback }}
                        @endif
                    </p>
                </div>
                <p class="font-headline-md text-headline-md text-primary">
                    {{ rtrim(rtrim(number_format($finalScore->score, 2), '0'), '.') }}
                </p>
            </div>
        @endif

        <div class="border-b border-outline-variant px-space-lg">
            <nav class="flex gap-space-lg -mb-px">
                <button
                    type="button"
                    @click="tab = 'grade'"
                    :class="tab === 'grade' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface'"
                    class="py-space-md border-b-2 font-label-md text-label-md transition-colors"
                >
                    Grade
                </button>
                @if ($isProctored)
                    <button
                        type="button"
                        @click="tab = 'review'"
                        :class="tab === 'review' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface'"
                        class="py-space-md border-b-2 font-label-md text-label-md transition-colors"
                    >
                        Review
                        @if ($proctorSession && $proctorSession->reviewed_at === null)
                            <span class="ml-space-xs inline-flex w-2 h-2 rounded-full bg-error align-middle"></span>
                        @endif
                    </button>
                @endif
            </nav>
        </div>

        <div x-show="tab === 'grade'" class="p-space-lg" x-data="{ confirmRegradeOpen: false }">
            @if ($answer && ! $isTakeHome)
                <div class="mb-space-lg">
                    <h4 class="font-label-md text-label-md text-on-surface mb-space-md">Answer</h4>
                    <div class="rte-content prose prose-sm max-w-none text-on-surface">{!! $answer->answer_text !!}</div>
                </div>
            @endif

            @if ($mcScore['count'] > 0)
                <div class="mb-space-lg p-space-md bg-surface-container rounded-lg flex items-center justify-between">
                    <div>
                        <p class="font-label-md text-label-md text-on-surface">{{ __('Multiple Choice Score') }}</p>
                        <p class="text-body-sm text-on-surface-variant">{{ $mcScore['correct'] }} / {{ $mcScore['count'] }} {{ __('correct') }}</p>
                    </div>
                    <p class="font-headline-sm text-headline-sm text-on-surface">
                        {{ rtrim(rtrim(number_format($mcScore['earned'], 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($mcScore['possible'], 2), '0'), '.') }}
                    </p>
                </div>
            @endif

            <form @submit.prevent="@js($alreadyGraded) ? confirmRegradeOpen = true : $wire.submitGrade()" class="space-y-space-md">
                <div class="space-y-space-md">
                    <div class="flex items-center justify-between">
                        <h4 class="font-label-md text-label-md text-on-surface">Question Scores</h4>
                    </div>

                    @if ($mcQuestionsCount > 0 && $essayQuestionsCount > 0)
                        <div class="flex gap-space-sm border-b border-outline-variant">
                            <button
                                type="button"
                                wire:click="$set('questionTab', 'multiple_choice')"
                                class="px-space-md py-space-sm border-b-2 font-label-md text-label-md transition-colors {{ $questionTab === 'multiple_choice' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}"
                            >
                                {{ __('Multiple Choice') }} ({{ $mcQuestionsCount }})
                            </button>
                            <button
                                type="button"
                                wire:click="$set('questionTab', 'essay')"
                                class="px-space-md py-space-sm border-b-2 font-label-md text-label-md transition-colors inline-flex items-center gap-space-xs {{ $questionTab === 'essay' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}"
                            >
                                {{ __('Essay') }} ({{ $essayQuestionsCount }})
                                @if ($essayUnscoredCount > 0)
                                    <span class="inline-flex items-center px-space-xs py-0.5 rounded-full text-body-xs font-medium bg-error/10 text-error">
                                        {{ __('Not graded') }} ({{ $essayUnscoredCount }})
                                    </span>
                                @endif
                            </button>
                        </div>
                    @endif

                    @unless ($isTakeHome)
                        <x-ui.pagination-links
                            :paginator="$paginatedQuestions"
                            perPageModel="questionsPerPage"
                            :perPageOptions="[5, 10, 25, 50]"
                        />
                    @endunless

                    <div wire:loading.remove wire:target="previousPage,nextPage,gotoPage,questionsPerPage,questionTab" class="space-y-space-md">
                        @foreach ($paginatedQuestions as $question)
                            @php($questionAnswer = $questionAnswers->get($question->id))
                            @php($needsScore = ! $alreadyGraded && $question->question_type->value === 'essay' && (($gradeQuestionScores[$question->id] ?? '') === ''))
                            <x-assessments.final-exam.question-grade-card
                                :question="$question"
                                :number="$paginatedQuestions->firstItem() + $loop->index"
                                :question-answer="$questionAnswer"
                                :needs-score="$needsScore"
                                :disabled="$alreadyGraded"
                            />
                        @endforeach
                    </div>

                    <div wire:loading.block wire:target="previousPage,nextPage,gotoPage,questionsPerPage,questionTab" class="space-y-space-md">
                        @php($skeletonRows = min($questionsPerPage, 5))
                        @for ($i = 0; $i < $skeletonRows; $i++)
                            <div class="space-y-space-sm">
                                <x-ui.skeleton-box class="h-4 w-full" />
                                <x-ui.skeleton-box class="h-4 w-2/3" />
                            </div>
                        @endfor
                    </div>

                    @unless ($isTakeHome)
                        <x-ui.pagination-links
                            :paginator="$paginatedQuestions"
                            perPageModel="questionsPerPage"
                            :perPageOptions="[5, 10, 25, 50]"
                        />
                    @endunless
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Feedback</label>
                    <textarea wire:model="gradeFeedback" rows="3" @disabled($alreadyGraded) class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:bg-surface-container disabled:text-on-surface-variant disabled:cursor-not-allowed"></textarea>
                </div>

                <div class="flex gap-space-md">
                    <a href="{{ route('assessments.final-exam.show', $assessment) }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="submitGrade"
                        :class="@js($alreadyGraded) ? 'opacity-50 cursor-not-allowed' : ''"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                    >
                        Save Grade
                    </button>
                </div>
            </form>

            <template x-teleport="body">
                <div
                    x-show="confirmRegradeOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[130] flex items-center justify-center bg-black/50 px-gutter"
                    @click.self="confirmRegradeOpen = false"
                >
                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ __('This exam has already been graded') }}</h2>
                        <p class="font-body-md text-body-md text-secondary">
                            {{ __('This exam has already been graded and cannot be graded again.') }}
                        </p>
                        <div class="flex items-center justify-end gap-space-md">
                            <button type="button" @click="confirmRegradeOpen = false" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">Close</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        @if ($isProctored)
            <div
                x-show="tab === 'review'"
                x-cloak
                class="p-space-lg space-y-space-md"
                x-data="{
                    confirmReReviewOpen: false,
                    cameraModalOpen: false,
                    screenModalOpen: false,
                    screenshotModalOpen: false,
                    lightboxUrl: null,
                    screenshotFilter: 'all',
                    screenshotGrouped: true,
                    screenshotSort: 'asc',
                    screenshotItems: [],
                    screenshotGroups: [],
                    screenshotEventTypeOptions: [],
                    screenshotsLoading: false,
                    screenshotsLoadingMore: false,
                    screenshotHasMore: false,
                    init() {
                        this.$watch('screenshotFilter', () => this.fetchScreenshots());
                        this.$watch('screenshotSort', () => this.fetchScreenshots());
                        this.$watch('screenshotGrouped', () => this.fetchScreenshots());
                    },
                    openScreenshots() {
                        this.screenshotModalOpen = true;
                        this.fetchScreenshots();
                    },
                    isGroupedView() {
                        return this.screenshotGrouped && this.screenshotFilter === 'all';
                    },
                    async fetchScreenshots() {
                        this.screenshotsLoading = true;
                        try {
                            if (this.isGroupedView()) {
                                const data = await $wire.loadProctorScreenshotGroups(this.screenshotSort, 5);
                                this.screenshotGroups = data.groups.map((group) => ({ ...group, loadingMore: false }));
                                this.screenshotEventTypeOptions = data.eventTypeOptions;
                                this.screenshotItems = [];
                                this.screenshotHasMore = false;
                            } else {
                                const eventType = this.screenshotFilter === 'all' ? null : this.screenshotFilter;
                                const data = await $wire.loadProctorScreenshots(eventType, this.screenshotSort, 0, 5);
                                this.screenshotItems = data.items;
                                this.screenshotEventTypeOptions = data.eventTypeOptions;
                                this.screenshotHasMore = data.hasMore;
                                this.screenshotGroups = [];
                            }
                        } finally {
                            this.screenshotsLoading = false;
                        }
                    },
                    async loadMoreScreenshots() {
                        if (! this.screenshotHasMore || this.screenshotsLoading || this.screenshotsLoadingMore) { return; }
                        this.screenshotsLoadingMore = true;
                        try {
                            const eventType = this.screenshotFilter === 'all' ? null : this.screenshotFilter;
                            const data = await $wire.loadProctorScreenshots(eventType, this.screenshotSort, this.screenshotItems.length, 5);
                            this.screenshotItems = [...this.screenshotItems, ...data.items];
                            this.screenshotHasMore = data.hasMore;
                        } finally {
                            this.screenshotsLoadingMore = false;
                        }
                    },
                    async loadMoreGroupScreenshots(group) {
                        if (! group.hasMore || group.loadingMore) { return; }
                        group.loadingMore = true;
                        try {
                            const data = await $wire.loadProctorScreenshots(group.eventType, this.screenshotSort, group.items.length, 5);
                            group.items = [...group.items, ...data.items];
                            group.hasMore = data.hasMore;
                        } finally {
                            group.loadingMore = false;
                        }
                    },
                    onScreenshotListScroll(e) {
                        if (this.isGroupedView()) { return; }
                        const el = e.target;
                        if (el.scrollTop + el.clientHeight >= el.scrollHeight - 100) {
                            this.loadMoreScreenshots();
                        }
                    },
                }"
            >
                @if ($proctorSession)
                    <div class="flex items-center gap-space-sm text-body-sm">
                        <span class="material-symbols-outlined text-[16px]">shield</span>
                        <span class="text-on-surface-variant">Proctoring:</span>
                        <span class="font-medium text-on-surface">{{ str($proctorSession->status->value)->title() }}</span>
                        <span class="text-on-surface-variant">&middot; {{ $proctorSession->events->count() }} event(s)</span>
                        @if ($proctorSession->review_decision)
                            <span class="inline-flex items-center px-space-sm py-0.5 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">
                                Review: {{ str($proctorSession->review_decision->value)->replace('_', ' ')->title() }}
                            </span>
                        @endif
                    </div>

                    @if ($cameraRecordings->isNotEmpty() || $screenRecordings->isNotEmpty() || $screenshotsCount > 0)
                        <div class="flex items-center gap-space-sm">
                            @if ($cameraRecordings->isNotEmpty())
                                <button type="button" @click="cameraModalOpen = true" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition inline-flex items-center gap-space-xs">
                                    <span class="material-symbols-outlined text-[16px]">videocam</span>
                                    Preview Camera ({{ $cameraRecordings->count() }})
                                </button>
                            @endif

                            @if ($screenRecordings->isNotEmpty())
                                <button type="button" @click="screenModalOpen = true" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition inline-flex items-center gap-space-xs">
                                    <span class="material-symbols-outlined text-[16px]">screen_share</span>
                                    Preview Screen Share ({{ $screenRecordings->count() }})
                                </button>
                            @endif

                            @if ($screenshotsCount > 0)
                                <button
                                    type="button"
                                    @click="openScreenshots()"
                                    class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition inline-flex items-center gap-space-xs"
                                >
                                    <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                                    Preview Screenshots ({{ $screenshotsCount }})
                                </button>
                            @endif
                        </div>

                        <template x-teleport="body">
                            <div
                                x-show="cameraModalOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 px-gutter"
                                @click.self="cameraModalOpen = false"
                            >
                                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-2xl w-full space-y-space-md">
                                    <div class="flex items-center justify-between">
                                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Camera Recording &middot; {{ $student->name }}</h2>
                                        <button type="button" @click="cameraModalOpen = false" class="text-on-surface-variant hover:text-on-surface">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                    <div class="space-y-space-sm max-h-[70vh] overflow-y-auto">
                                        @foreach ($cameraRecordings as $recording)
                                            <video controls preload="none" class="w-full rounded-lg bg-black aspect-video" src="{{ $this->recordingUrl($recording->file_url) }}"></video>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-teleport="body">
                            <div
                                x-show="screenModalOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 px-gutter"
                                @click.self="screenModalOpen = false"
                            >
                                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-2xl w-full space-y-space-md">
                                    <div class="flex items-center justify-between">
                                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Screen Share Recording &middot; {{ $student->name }}</h2>
                                        <button type="button" @click="screenModalOpen = false" class="text-on-surface-variant hover:text-on-surface">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                    <div class="space-y-space-sm max-h-[70vh] overflow-y-auto">
                                        @foreach ($screenRecordings as $recording)
                                            <video controls preload="none" class="w-full rounded-lg bg-black aspect-video" src="{{ $this->recordingUrl($recording->file_url) }}"></video>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-teleport="body">
                            <div
                                x-show="screenshotModalOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-[110] bg-surface flex flex-col"
                            >
                                <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant flex-shrink-0 gap-space-md flex-wrap">
                                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Event Screenshots &middot; {{ $student->name }}</h2>

                                    <div class="flex items-center gap-space-sm flex-wrap">
                                        <select x-model="screenshotFilter" class="px-space-sm py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface bg-surface">
                                            <option value="all">All event types</option>
                                            <template x-for="eventType in screenshotEventTypeOptions" :key="eventType">
                                                <option :value="eventType" x-text="eventType === 'none' ? 'Other' : eventType.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())"></option>
                                            </template>
                                        </select>

                                        <button
                                            type="button"
                                            x-show="screenshotFilter !== 'all'"
                                            x-cloak
                                            @click="screenshotFilter = 'all'"
                                            class="inline-flex items-center gap-space-xs px-space-sm py-xs font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                            Clear filter
                                        </button>

                                        <select x-model="screenshotSort" class="px-space-sm py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface bg-surface">
                                            <option value="asc">Oldest first</option>
                                            <option value="desc">Newest first</option>
                                        </select>

                                        <label class="inline-flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
                                            <input type="checkbox" x-model="screenshotGrouped" class="w-4 h-4 accent-primary" />
                                            Group by event
                                        </label>

                                        <button type="button" @click="screenshotModalOpen = false" class="text-on-surface-variant hover:text-on-surface">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex-1 overflow-y-auto p-space-lg space-y-space-md" @scroll.debounce.150ms="onScreenshotListScroll($event)">
                                    <template x-if="screenshotsLoading">
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-space-sm">
                                            <template x-for="n in 6" :key="n">
                                                <div class="animate-pulse bg-surface-container rounded-lg aspect-video"></div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="!screenshotsLoading && isGroupedView() && screenshotGroups.length === 0">
                                        <p class="text-body-sm text-on-surface-variant text-center">No screenshots recorded.</p>
                                    </template>

                                    <template x-if="!screenshotsLoading && ! isGroupedView() && screenshotItems.length === 0">
                                        <p class="text-body-sm text-on-surface-variant text-center">No screenshots recorded.</p>
                                    </template>

                                    <template x-if="!screenshotsLoading && isGroupedView() && screenshotGroups.length > 0">
                                        <div class="space-y-space-md">
                                            <template x-for="group in screenshotGroups" :key="group.eventType">
                                                <div class="border border-outline-variant rounded-lg p-space-md space-y-space-sm">
                                                    <p class="font-label-sm text-label-sm text-on-surface">
                                                        <span x-text="group.label"></span>
                                                        <span class="text-body-xs text-on-surface-variant font-normal" x-text="'× ' + group.total"></span>
                                                    </p>
                                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-space-sm">
                                                        <template x-for="item in group.items" :key="item.url">
                                                            <div class="space-y-space-xs">
                                                                <div class="grid grid-rows-2 gap-space-xs">
                                                                    <div x-data="{ loaded: false }" class="relative">
                                                                        <template x-if="item.cameraUrl">
                                                                            <button
                                                                                type="button"
                                                                                @click="lightboxUrl = item.cameraUrl"
                                                                                class="relative block w-full aspect-video rounded-lg overflow-hidden bg-surface-container cursor-zoom-in"
                                                                            >
                                                                                <div x-show="!loaded" x-cloak class="absolute inset-0 animate-pulse bg-surface-container"></div>
                                                                                <img loading="lazy" @load="loaded = true" :class="loaded ? 'opacity-100' : 'opacity-0'" class="w-full h-full rounded-lg bg-black object-cover transition-opacity duration-300" :src="item.cameraUrl" alt="Proctor camera screenshot" />
                                                                            </button>
                                                                        </template>
                                                                        <template x-if="!item.cameraUrl">
                                                                            <div class="w-full aspect-video rounded-lg bg-surface-container flex items-center justify-center text-body-xs text-on-surface-variant">No camera</div>
                                                                        </template>
                                                                    </div>
                                                                    <div x-data="{ loaded: false }" class="relative">
                                                                        <button
                                                                            type="button"
                                                                            @click="lightboxUrl = item.url"
                                                                            class="relative block w-full aspect-video rounded-lg overflow-hidden bg-surface-container cursor-zoom-in"
                                                                        >
                                                                            <div x-show="!loaded" x-cloak class="absolute inset-0 animate-pulse bg-surface-container"></div>
                                                                            <img loading="lazy" @load="loaded = true" :class="loaded ? 'opacity-100' : 'opacity-0'" class="w-full h-full rounded-lg bg-black object-cover transition-opacity duration-300" :src="item.url" alt="Proctor screen screenshot" />
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <p class="text-body-xs text-on-surface-variant text-center" x-text="item.capturedAt"></p>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        x-show="group.hasMore"
                                                        x-cloak
                                                        @click="loadMoreGroupScreenshots(group)"
                                                        :disabled="group.loadingMore"
                                                        class="text-body-xs text-primary hover:underline disabled:opacity-50 inline-flex items-center gap-space-xs"
                                                    >
                                                        <span x-show="group.loadingMore" x-cloak class="material-symbols-outlined animate-spin text-[14px]">progress_activity</span>
                                                        <span x-text="group.loadingMore ? 'Loading…' : 'Load more'"></span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="!screenshotsLoading && ! isGroupedView() && screenshotItems.length > 0">
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-space-sm">
                                            <template x-for="item in screenshotItems" :key="item.url">
                                                <div class="space-y-space-xs">
                                                    <div class="grid grid-rows-2 gap-space-xs">
                                                        <div x-data="{ loaded: false }" class="relative">
                                                            <template x-if="item.cameraUrl">
                                                                <button
                                                                    type="button"
                                                                    @click="lightboxUrl = item.cameraUrl"
                                                                    class="relative block w-full aspect-video rounded-lg overflow-hidden bg-surface-container cursor-zoom-in"
                                                                >
                                                                    <div x-show="!loaded" x-cloak class="absolute inset-0 animate-pulse bg-surface-container"></div>
                                                                    <img loading="lazy" @load="loaded = true" :class="loaded ? 'opacity-100' : 'opacity-0'" class="w-full h-full rounded-lg bg-black object-cover transition-opacity duration-300" :src="item.cameraUrl" alt="Proctor camera screenshot" />
                                                                </button>
                                                            </template>
                                                            <template x-if="!item.cameraUrl">
                                                                <div class="w-full aspect-video rounded-lg bg-surface-container flex items-center justify-center text-body-xs text-on-surface-variant">No camera</div>
                                                            </template>
                                                        </div>
                                                        <div x-data="{ loaded: false }" class="relative">
                                                            <button
                                                                type="button"
                                                                @click="lightboxUrl = item.url"
                                                                class="relative block w-full aspect-video rounded-lg overflow-hidden bg-surface-container cursor-zoom-in"
                                                            >
                                                                <div x-show="!loaded" x-cloak class="absolute inset-0 animate-pulse bg-surface-container"></div>
                                                                <img loading="lazy" @load="loaded = true" :class="loaded ? 'opacity-100' : 'opacity-0'" class="w-full h-full rounded-lg bg-black object-cover transition-opacity duration-300" :src="item.url" alt="Proctor screen screenshot" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <p class="text-body-xs text-on-surface-variant text-center" x-text="item.capturedAt + ' · ' + item.eventTypeLabel"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="screenshotsLoadingMore">
                                        <div class="flex justify-center pt-space-sm">
                                            <span class="material-symbols-outlined animate-spin text-on-surface-variant text-[20px]">progress_activity</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-teleport="body">
                            <div
                                x-show="lightboxUrl"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-[120] bg-black/90 flex items-center justify-center"
                                @click.self="lightboxUrl = null"
                                @keydown.escape.window="lightboxUrl = null"
                            >
                                <button type="button" @click="lightboxUrl = null" class="absolute top-space-lg right-space-lg text-white/80 hover:text-white">
                                    <span class="material-symbols-outlined text-[32px]">close</span>
                                </button>
                                <img :src="lightboxUrl" class="max-w-[95vw] max-h-[95vh] object-contain" alt="Proctor screenshot full view" />
                            </div>
                        </template>
                    @endif

                    <div class="flex items-end gap-space-sm">
                        <div class="flex-1">
                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Decision</label>
                            <select wire:model="reviewDecision.{{ $proctorSession->id }}" @disabled($alreadyReviewed) class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-sm text-body-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="no_action" @selected(($proctorSession->review_decision?->value ?? 'no_action') === 'no_action')>No Action</option>
                                <option value="warning" @selected($proctorSession->review_decision?->value === 'warning')>Warning</option>
                                <option value="disqualified" @selected($proctorSession->review_decision?->value === 'disqualified')>Disqualified</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Notes</label>
                            <input type="text" wire:model="reviewNotes.{{ $proctorSession->id }}" value="{{ $proctorSession->review_notes }}" @disabled($alreadyReviewed) class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-sm text-body-sm disabled:opacity-50 disabled:cursor-not-allowed" />
                        </div>
                        <button
                            type="button"
                            wire:loading.attr="disabled"
                            wire:target="reviewProctorSession"
                            @click="@js($alreadyReviewed) ? confirmReReviewOpen = true : $wire.reviewProctorSession('{{ $proctorSession->id }}')"
                            :class="@js($alreadyReviewed) ? 'opacity-50 cursor-not-allowed' : ''"
                            class="px-space-md py-space-sm bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 flex-shrink-0"
                        >
                            Save
                        </button>
                    </div>

                    <template x-teleport="body">
                        <div
                            x-show="confirmReReviewOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[130] flex items-center justify-center bg-black/50 px-gutter"
                            @click.self="confirmReReviewOpen = false"
                        >
                            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                                <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ __('This proctoring session has already been reviewed') }}</h2>
                                <p class="font-body-md text-body-md text-secondary">
                                    {{ __('This proctoring session has already been reviewed and cannot be reviewed again.') }}
                                </p>
                                <div class="flex items-center justify-end gap-space-md">
                                    <button type="button" @click="confirmReReviewOpen = false" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">Close</button>
                                </div>
                            </div>
                        </div>
                    </template>
                @else
                    <p class="text-body-sm text-on-surface-variant">No proctoring session recorded for this attempt.</p>
                @endif
            </div>
        @endif

        <x-ui.material-preview-modals />
    </div>
</div>
