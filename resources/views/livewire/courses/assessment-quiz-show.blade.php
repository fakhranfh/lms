@section('title', $assessment->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div>
        <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Assessments
        </a>
    </div>

    @if ($successMessage)
        <template x-teleport="body">
            <div
                x-data="{ open: true }"
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
                @click.self="open = false; $wire.call('clearSuccessMessage')"
            >
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200 delay-75"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg text-center"
                >
                    <div class="mx-auto w-12 h-12 rounded-full bg-success/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-success text-[28px]" data-weight="fill">check_circle</span>
                    </div>
                    <div>
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-space-xs">Submission successful</h2>
                        <p class="font-body-md text-body-md text-secondary">{{ $successMessage }}</p>
                    </div>
                    <button
                        type="button"
                        @click="open = false; $wire.call('clearSuccessMessage')"
                        class="w-full px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        OK
                    </button>
                </div>
            </div>
        </template>
    @endif

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Overview Card -->
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <!-- Header with title and badges -->
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-space-md">
                    <h1 class="font-headline-md text-headline-md text-on-surface">{{ $assessment->title }}</h1>
                    @if ($isExpired)
                        <span class="inline-flex items-center px-space-sm py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">
                            Expired
                        </span>
                    @endif
                </div>
                @if ($isStudent && $currentScore)
                    <p class="text-body-sm text-on-surface-variant mt-space-xs">
                        Final Score: <span class="font-medium text-on-surface">{{ rtrim(rtrim(number_format($currentScore->score, 1), '0'), '.') }} pts</span>
                    </p>
                @endif
            </div>

            @if (!$isStudent && $canEdit)
                <a
                    href="{{ route('assessments.quiz.edit', $assessment) }}"
                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity flex-shrink-0"
                >
                    Edit
                </a>
            @endif
        </div>

        <!-- Meta grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-space-lg border-t border-b border-outline-variant py-space-lg">
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Start</p>
                <p class="text-body-sm text-on-surface font-medium">
                    @if ($assessment->start_date)
                        {{ $assessment->start_date_display->format('M j, Y, H:i') }}
                    @else
                        <span class="text-on-surface-variant">—</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Due</p>
                <p class="text-body-sm text-on-surface font-medium">
                    @if ($assessment->end_date)
                        {{ $assessment->end_date_display->format('M j, Y, H:i') }}
                    @else
                        <span class="text-on-surface-variant">—</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Total Question</p>
                <p class="text-body-sm text-on-surface font-medium">{{ $quiz->questions->count() }}</p>
            </div>
            @if ($isStudent)
                <div>
                    <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Total Attempts</p>
                    <p class="text-body-sm text-on-surface font-medium">{{ $attemptsUsed }} of {{ $attemptLimit }} Attempts</p>
                </div>
            @endif
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Time Limit</p>
                <p class="text-body-sm text-on-surface font-medium">
                    {{ $quiz->time_limit_per_attempt ? $quiz->time_limit_per_attempt.' min' : 'Unlimited' }}
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Scoring Method</p>
                <p class="text-body-sm text-on-surface font-medium">{{ str($quiz->scoring_method->value)->title() }}</p>
            </div>
        </div>

        @if ($instruction?->content)
            <div class="bg-surface-container/50 border border-outline-variant rounded-lg p-space-lg">
                <p class="font-label-md text-label-md text-on-surface mb-space-sm">Instructions</p>
                <div class="rte-content prose prose-sm max-w-none text-on-surface-variant">{!! $instruction->content !!}</div>
            </div>
        @endif

        <!-- Current Score Card -->
        @if ($isStudent && $currentScore)
            <div class="bg-primary rounded-lg p-space-lg text-on-primary space-y-space-md">
                <div>
                    <p class="text-body-sm opacity-90 mb-space-sm">Current Score</p>
                    <p class="text-headline-lg font-bold">{{ rtrim(rtrim(number_format($currentScore->score, 1), '0'), '.') }} <span class="text-body-md font-normal">pts</span></p>
                </div>
                @if ($currentScore->feedback)
                    <div class="pt-space-md border-t border-on-primary/20">
                        <p class="text-body-xs opacity-90 mb-space-sm">Feedback</p>
                        <p class="text-body-sm">{{ $currentScore->feedback }}</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Status Message & Action Button -->
        @if ($isStudent)
            @if ($inProgress)
                <template x-teleport="body">
                    <div
                        x-data="{
                            deadline: @js($deadlineIso),
                            remaining: null,
                            timer: null,
                            submitting: false,
                            confirmOpen: false,
                            tick() {
                                if (! this.deadline) { return; }
                                let diff = Math.floor((new Date(this.deadline) - new Date()) / 1000);
                                this.remaining = Math.max(diff, 0);
                                if (diff <= 0) {
                                    clearInterval(this.timer);
                                    if (! this.submitting) {
                                        this.submitting = true;
                                        $wire.submitAttempt();
                                    }
                                }
                            },
                            formatted() {
                                if (this.remaining === null) { return ''; }
                                let m = Math.floor(this.remaining / 60).toString().padStart(2, '0');
                                let s = (this.remaining % 60).toString().padStart(2, '0');
                                return m + ':' + s;
                            },
                        }"
                        x-init="tick(); timer = setInterval(() => tick(), 1000)"
                        x-on:destroy="clearInterval(timer)"
                        class="fixed inset-0 z-[100] bg-surface flex flex-col"
                    >
                        <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant flex-shrink-0">
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">Attempt {{ $inProgress->attempt_number }} — {{ $assessment->title }}</h2>

                            <div class="flex items-center gap-space-lg">
                                @if ($deadlineIso)
                                    <div class="flex items-center gap-space-xs font-label-md text-label-md" :class="remaining !== null && remaining <= 60 ? 'text-error' : 'text-on-surface'">
                                        <span class="material-symbols-outlined text-[18px]">timer</span>
                                        <span x-text="formatted()"></span>
                                    </div>
                                @endif

                                <button
                                    type="submit"
                                    form="quiz-attempt-form"
                                    wire:loading.attr="disabled"
                                    wire:target="submitAttempt"
                                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                                >
                                    <span wire:loading wire:target="submitAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                    Submit Quiz
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-[220px_1fr]" x-data="{ currentQuestion: -1 }">
                            <!-- Left: Question Navigator -->
                            <div class="overflow-y-auto p-space-lg border-b md:border-b-0 md:border-r border-outline-variant">
                                <button
                                    type="button"
                                    @click="currentQuestion = -1"
                                    :class="currentQuestion === -1 ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface hover:bg-surface-container/70'"
                                    class="w-full mb-space-lg px-space-md py-space-sm rounded-lg font-label-sm text-label-sm flex items-center gap-space-xs transition"
                                >
                                    <span class="material-symbols-outlined text-[16px]">info</span>
                                    Instructions
                                </button>

                                <p class="font-label-sm text-label-sm text-secondary mb-space-md">Questions</p>
                                <div class="grid grid-cols-6 md:grid-cols-4 gap-space-xs">
                                    @foreach ($quiz->questions as $question)
                                        <button
                                            type="button"
                                            @click="currentQuestion = {{ $loop->index }}"
                                            :class="currentQuestion === {{ $loop->index }} ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface hover:bg-surface-container/70'"
                                            class="w-10 h-10 rounded-lg font-label-sm text-label-sm flex items-center justify-center transition"
                                        >
                                            {{ $loop->iteration }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Right: Current Question -->
                            <div class="overflow-y-auto p-space-xl">
                                <form id="quiz-attempt-form" @submit.prevent="confirmOpen = true">
                                    <div x-show="currentQuestion === -1" x-cloak class="space-y-space-lg max-w-2xl mx-auto">
                                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Instructions</h3>
                                        @if ($instruction?->content)
                                            <div class="rte-content prose prose-lg max-w-none text-on-surface">{!! $instruction->content !!}</div>
                                        @else
                                            <p class="text-body-md text-on-surface-variant">No special instructions for this quiz. Good luck!</p>
                                        @endif
                                        <button
                                            type="button"
                                            @click="currentQuestion = 0"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                                        >
                                            Start Questions
                                        </button>
                                    </div>

                                    @foreach ($quiz->questions as $question)
                                        <div x-show="currentQuestion === {{ $loop->index }}" x-cloak class="space-y-space-lg max-w-2xl mx-auto">
                                            <p class="text-body-sm text-on-surface-variant">Question {{ $loop->iteration }} of {{ $quiz->questions->count() }}</p>
                                            <div class="rte-content prose prose-lg max-w-none text-on-surface">{!! $question->description !!}</div>

                                            @if (in_array($question->question_type->value, ['multiple_choice', 'true_false']))
                                                <div class="space-y-space-md">
                                                    @foreach ($question->options as $option)
                                                        <label class="flex items-center gap-space-md p-space-lg border border-outline rounded-lg cursor-pointer hover:bg-surface-container/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5 transition">
                                                            <input type="radio" name="answer-{{ $question->id }}" wire:model="answers.{{ $question->id }}" value="{{ $option->id }}" class="w-5 h-5 accent-primary flex-shrink-0" />
                                                            <span class="rte-content text-body-lg text-on-surface">{!! $option->label !!}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @else
                                                <textarea wire:model="answers.{{ $question->id }}" rows="8" class="w-full px-space-lg py-space-md border border-outline rounded-lg font-body-lg text-body-lg focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                                            @endif
                                        </div>
                                    @endforeach

                                    <div x-show="currentQuestion > -1" class="flex items-center justify-between pt-space-lg mt-space-lg border-t border-outline-variant max-w-2xl mx-auto">
                                        <button
                                            type="button"
                                            @click="currentQuestion = Math.max(currentQuestion - 1, -1)"
                                            class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                                        >
                                            Previous
                                        </button>

                                        <button
                                            type="button"
                                            x-show="currentQuestion < {{ $quiz->questions->count() - 1 }}"
                                            @click="currentQuestion = Math.min(currentQuestion + 1, {{ $quiz->questions->count() - 1 }})"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Submit Confirmation -->
                        <div
                            x-show="confirmOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 px-gutter"
                            @click.self="confirmOpen = false"
                        >
                            <div
                                x-show="confirmOpen"
                                x-transition:enter="transition ease-out duration-200 delay-75"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg"
                            >
                                <h2 class="font-headline-sm text-headline-sm text-on-surface">Submit confirmation</h2>
                                <p class="font-body-md text-body-md text-secondary">
                                    Are you sure you want to submit this quiz? You will not be able to change your answers afterwards.
                                </p>
                                <div class="flex items-center justify-end gap-space-md">
                                    <button type="button" @click="confirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                                    <button
                                        type="button"
                                        wire:loading.attr="disabled"
                                        wire:target="submitAttempt"
                                        @click="confirmOpen = false; submitting = true; clearInterval(timer); $wire.submitAttempt()"
                                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                                    >
                                        <span wire:loading wire:target="submitAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                        Submit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            @elseif ($isExpired)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">The submission window for this quiz has closed.</p>
                    </div>
                </div>
            @elseif (!$canStart)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">You have reached the maximum number of attempts for this quiz.</p>
                    </div>
                </div>
            @else
                <div x-data="{ confirmOpen: false }">
                    <button
                        type="button"
                        @click="confirmOpen = true"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        Start Attempt
                    </button>

                    <template x-teleport="body">
                        <div
                            x-show="confirmOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
                            @click.self="confirmOpen = false"
                        >
                            <div
                                x-show="confirmOpen"
                                x-transition:enter="transition ease-out duration-200 delay-75"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg"
                            >
                                <h2 class="font-headline-sm text-headline-sm text-on-surface">Start attempt confirmation</h2>
                                <p class="font-body-md text-body-md text-secondary">
                                    Are you sure you want to start this quiz?
                                    @if ($quiz->time_limit_per_attempt)
                                        You will have {{ $quiz->time_limit_per_attempt }} minutes to finish once you begin.
                                    @endif
                                </p>
                                <div class="flex items-center justify-end gap-space-md">
                                    <button type="button" @click="confirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                                    <button
                                        type="button"
                                        wire:loading.attr="disabled"
                                        wire:target="startAttempt"
                                        @click="confirmOpen = false; $wire.startAttempt()"
                                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                                    >
                                        <span wire:loading wire:target="startAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                        Start Attempt
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endif
        @endif
    </div>

    <!-- Answer Attempts Section -->
    @if ($isStudent && $attemptRows->isNotEmpty())
        <div class="space-y-space-md">
            <h2 class="font-label-lg text-label-lg text-on-surface">Answer Attempts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                @foreach ($attemptRows as $row)
                    <div
                        class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md cursor-pointer hover:bg-surface-container/50 transition"
                        x-data="{ open: false }"
                        @click="open = true"
                    >
                        <div class="flex items-start justify-between gap-space-md">
                            <div class="flex-1">
                                <h3 class="font-label-lg text-label-lg text-on-surface mb-space-xs">Attempt {{ $row['attempt']->attempt_number }}</h3>
                                <p class="text-body-xs text-on-surface-variant">Submitted {{ $row['attempt']->submitted_at_display?->format('M j, Y H:i') }}</p>
                            </div>
                            <div class="bg-primary rounded-lg p-space-md text-on-primary text-center min-w-[140px] flex-shrink-0">
                                <p class="text-body-xs opacity-90 mb-space-xs">SCORE</p>
                                <p class="text-headline-sm font-bold">{{ rtrim(rtrim(number_format($row['total'], 1), '0'), '.') }} <span class="text-body-xs font-normal">pts</span></p>
                                @if ($row['pending'])
                                    <p class="text-body-xs opacity-75 mt-space-xs">Excludes ungraded questions</p>
                                @endif
                            </div>
                        </div>

                        <x-assessments.quiz-review.modal
                            show="open"
                            onClose="open = false"
                            :title="'Attempt '.$row['attempt']->attempt_number.' Answers — '.$assessment->title"
                            :questions="$quiz->questions"
                            :answers="$row['answers']"
                            :total="$row['total']"
                            :feedback="$row['score']?->feedback"
                        />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Teacher: Submissions List -->
    @if (!$isStudent)
        <div class="space-y-space-md">
            <h2 class="font-label-lg text-label-lg text-on-surface font-bold">Student Submissions</h2>

            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                <x-ui.pagination-links
                    :paginator="$studentRows"
                    perPageModel="perPage"
                    searchModel="studentSearch"
                    searchPlaceholder="Search by name"
                    :search="$studentSearch"
                    class="p-space-md border-b border-outline-variant"
                />

                <div class="flex items-center gap-space-sm p-space-md border-b border-outline-variant">
                    <label class="text-body-sm text-on-surface-variant" for="submissionFilter">Status</label>
                    <select id="submissionFilter" wire:model.live="submissionFilter" class="h-9 px-space-sm rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none">
                        <option value="">All</option>
                        <option value="not_submitted">Not Submitted</option>
                        <option value="submitted">Ungraded</option>
                        <option value="graded">Graded</option>
                    </select>
                </div>

                <x-ui.person-grid-skeleton
                    :rows="9"
                    wire:loading.grid
                    wire:target="previousPage,nextPage,gotoPage,perPage,studentSearch,submissionFilter"
                />

                <x-ui.person-grid wire:loading.remove wire:target="previousPage,nextPage,gotoPage,perPage,studentSearch,submissionFilter">
                    @forelse ($studentRows as $row)
                        <div
                            wire:key="student-{{ $row['user']->id }}"
                            class="bg-surface p-space-lg {{ $row['attempt'] ? 'cursor-pointer hover:bg-surface-container/50 transition' : '' }}"
                            @if ($row['attempt']) x-data="{ open: false }" @click="open = true" @endif
                        >
                            <div class="flex flex-col items-center text-center gap-space-sm">
                                <x-avatar :user="$row['user']" size="12" />
                                <p class="font-label-lg text-label-lg text-on-surface">{{ $row['user']->name }}</p>
                                @if ($row['attempt'])
                                    <p class="text-body-sm text-on-surface-variant">
                                        {{ $row['attemptCount'] }} attempt(s) &middot; submitted {{ $row['attempt']->submitted_at_display?->format('M j, Y H:i') }}
                                    </p>
                                @endif

                                <span class="inline-flex items-center px-space-md py-space-xs rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">
                                    {{ $row['score'] ? 'Score: '.rtrim(rtrim(number_format($row['score']->score, 2), '0'), '.') : ($row['attempt'] ? 'Ungraded' : 'Not submitted') }}
                                    @if ($row['pending']) &middot; Excludes ungraded questions @endif
                                </span>
                            </div>

                            @if ($row['attempt'])
                                <x-assessments.quiz-review.modal
                                    show="open"
                                    onClose="open = false"
                                    :title="$row['user']->name.' — Attempt '.$row['attempt']->attempt_number"
                                    :questions="$quiz->questions"
                                    :answers="$row['answers']"
                                    :total="$row['total']"
                                    :feedback="$row['score']?->feedback"
                                />
                            @endif
                        </div>
                    @empty
                        <div class="bg-surface p-space-lg text-center text-body-sm text-on-surface-variant col-span-full">
                            {{ trim($studentSearch) !== '' || $submissionFilter !== '' ? 'No students match your filters.' : 'No students enrolled.' }}
                        </div>
                    @endforelse
                    <x-ui.person-grid-filler :count="$studentRows->count()" />
                </x-ui.person-grid>

                <x-ui.pagination-links
                    :paginator="$studentRows"
                    class="p-space-md border-t border-outline-variant"
                />
            </div>
        </div>
    @endif
</div>
