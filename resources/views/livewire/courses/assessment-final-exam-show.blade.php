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
                <div class="flex items-center gap-space-md mb-space-md">
                    <h1 class="font-headline-md text-headline-md text-on-surface">{{ $assessment->title }}</h1>
                    @if ($isExpired)
                        <span class="inline-flex items-center px-space-sm py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">
                            Expired
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-space-md">
                    <span class="inline-flex items-center gap-space-xs text-body-sm text-on-surface-variant">
                        <span class="material-symbols-outlined text-[16px]">
                            {{ $assessment->assigned_to->value === 'individual' ? 'person' : 'groups' }}
                        </span>
                        {{ str($assessment->assigned_to->value)->title() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Meta grid (2 columns) -->
        <div class="grid grid-cols-2 gap-space-lg border-t border-b border-outline-variant py-space-lg">
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
                <p class="text-body-sm text-on-surface font-medium">{{ $assessment->questions->count() }}</p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Total Attempts</p>
                <p class="text-body-sm text-on-surface font-medium">
                    @if ($isStudent)
                        {{ $attemptsUsed }} of {{ $attemptLimit }} Attempts
                    @else
                        —
                    @endif
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Scoring Method</p>
                <p class="text-body-sm text-on-surface font-medium">Latest Score</p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Exam Type</p>
                <p class="text-body-sm text-on-surface font-medium">
                    {{ $finalExam ? str($finalExam->exam_type->value)->replace('_', ' ')->title() : '—' }}
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Local Files</p>
                <p class="text-body-sm text-on-surface font-medium">
                    {{ $finalExam && $finalExam->allow_local_files ? 'Allowed' : 'Not allowed' }}
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Internet</p>
                <p class="text-body-sm text-on-surface font-medium">
                    {{ $finalExam && $finalExam->allow_internet ? 'Allowed' : 'Not allowed' }}
                </p>
            </div>
        </div>

        @if ($finalExam?->instructions)
            <div class="bg-surface-container/50 border border-outline-variant rounded-lg p-space-lg">
                <p class="font-label-md text-label-md text-on-surface mb-space-sm">Instructions</p>
                <div class="rte-content prose prose-sm max-w-none text-on-surface-variant">{!! $finalExam->instructions !!}</div>
            </div>
        @endif

        <!-- Latest Score Card -->
        @if ($isStudent && $latestScore)
            <div class="bg-primary rounded-lg p-space-lg text-on-primary space-y-space-md">
                <div>
                    <p class="text-body-sm opacity-90 mb-space-sm">Latest Score</p>
                    <p class="text-headline-lg font-bold">{{ rtrim(rtrim(number_format($latestScore->score, 1), '0'), '.') }} <span class="text-body-md font-normal">pts</span></p>
                    <p class="text-body-xs opacity-75 mt-space-md">Score Updated On: {{ $latestScore->graded_at_display?->format('j M Y, H:i') ?? '—' }}</p>
                </div>
                @if ($latestScore->feedback)
                    <div class="pt-space-md border-t border-on-primary/20">
                        <p class="text-body-xs opacity-90 mb-space-sm">Feedback</p>
                        <p class="text-body-sm">{{ $latestScore->feedback }}</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Status Message & Action Button -->
        @if ($isStudent)
            @if ($latestScore)
                <div class="p-space-lg bg-surface-container/50 border border-outline-variant rounded-lg">
                    <p class="text-body-sm text-on-surface-variant">The score for this assessment has been approved. You cannot start another attempt.</p>
                </div>
            @elseif (!$canResubmit && $latestAttempt)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">Your submission is awaiting grading. You will be able to resubmit once graded.</p>
                    </div>
                </div>
            @elseif ($isExpired)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">The submission window for this assessment has closed.</p>
                    </div>
                </div>
            @elseif ($attemptLimit && $attemptsUsed >= $attemptLimit)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">You have reached the maximum number of attempts for this assessment.</p>
                    </div>
                </div>
            @elseif ($canSubmit && $canResubmit && $finalExam && in_array($finalExam->exam_type->value, ['open_book', 'closed_book']))
                @if ($assessment->quiz && $assessment->quiz->questions->isNotEmpty())
                    <div x-data="{ confirmOpen: false, navigating: false }">
                        <button
                            type="button"
                            @click="confirmOpen = true"
                            :disabled="navigating"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                        >
                            {{ $latestAttempt ? 'Continue Exam' : 'Start Exam' }}
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
                                <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
                                    <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $latestAttempt ? 'Continue Exam?' : 'Start Exam?' }}</h2>
                                    <p class="font-body-md text-body-md text-secondary">
                                        This is a proctored exam. You'll first go through pre-flight checks (internet speed, camera, microphone, screen sharing) before the exam begins.
                                    </p>
                                    <div class="flex items-center justify-end gap-space-md">
                                        <button type="button" @click="confirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                                        <button
                                            type="button"
                                            :disabled="navigating"
                                            @click="navigating = true; Livewire.navigate('{{ route('assessments.final-exam.proctor.preflight', $assessment) }}')"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                                        >
                                            <span x-show="navigating" x-cloak class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                            {{ $latestAttempt ? 'Continue Exam' : 'Start Exam' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @else
                    <div class="p-space-lg bg-surface-container/50 border border-outline-variant rounded-lg">
                        <p class="text-body-sm text-on-surface-variant">This exam isn't ready yet. Please check back later.</p>
                    </div>
                @endif
            @elseif ($canSubmit && $canResubmit)
                <div x-data="{ attemptOpen: false }">
                    <button
                        type="button"
                        @click="attemptOpen = true"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        {{ $latestAttempt ? 'Continue Attempt' : 'Start Attempt' }}
                    </button>

                    <template x-teleport="body">
                        <div
                            x-data="{ confirmOpen: false, answerEmpty: false }"
                            x-show="attemptOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[100] bg-surface flex flex-col"
                        >
                            <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant">
                                <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $latestAttempt ? 'Resubmit' : 'Submit' }} Answer &mdash; {{ $assessment->title }}</h2>
                                <button type="button" @click="attemptOpen = false" class="p-2 hover:bg-surface-container rounded transition">
                                    <span class="material-symbols-outlined text-on-surface-variant">close</span>
                                </button>
                            </div>

                            <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-2">
                                <!-- Left: Questions -->
                                <div class="overflow-y-auto p-space-lg border-b md:border-b-0 md:border-r border-outline-variant divide-y divide-outline-variant">
                                    @foreach ($assessment->questions as $question)
                                        <div class="space-y-space-md {{ $loop->first ? '' : 'pt-space-lg' }} {{ $loop->last ? '' : 'pb-space-lg' }}">
                                            <p class="text-body-xs text-on-surface-variant mb-space-sm">Question {{ $loop->iteration }} &middot; {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts</p>
                                            <div class="rte-content prose prose-sm max-w-none text-on-surface">{!! $question->description !!}</div>
                                            @if ($question->files->isNotEmpty())
                                                <div class="mt-space-md space-y-space-xs">
                                                    @foreach ($question->files as $file)
                                                        <div class="flex items-center gap-space-xs text-body-sm text-on-surface-variant">
                                                            <span class="material-symbols-outlined text-[16px]">description</span>
                                                            {{ $file->title }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Right: Answer Input -->
                                <div class="overflow-y-auto p-space-lg">
                                    <form
                                        @submit.prevent="
                                            answerEmpty = ($wire.answerText || '').replace(/<[^>]*>/g, '').trim() === '';
                                            if (!answerEmpty) { confirmOpen = true; }
                                        "
                                        class="space-y-space-md"
                                    >
                                        <label class="block font-label-md text-label-md text-on-surface">{{ $latestAttempt ? 'Resubmit' : 'Submit' }} Answer</label>
                                        <div @input.capture="answerEmpty = false">
                                            <x-rich-text-editor id="answer" wire-model="answerText" :value="$answerText" />
                                            <p x-show="answerEmpty" x-cloak class="text-body-xs text-error mt-space-xs">{{ __('Answer cannot be empty.') }}</p>
                                            @error('answerText') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                        </div>
                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            wire:target="submit"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                                        >
                                            {{ $latestAttempt ? 'Resubmit' : 'Submit' }}
                                        </button>
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
                                    <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $latestAttempt ? 'Resubmit' : 'Submit' }} confirmation</h2>
                                    <p class="font-body-md text-body-md text-secondary">
                                        {{ $latestAttempt
                                            ? 'Are you sure you want to resubmit your answer? This will replace your previous submission.'
                                            : 'Are you sure you want to submit your answer? You will not be able to edit it once graded.' }}
                                    </p>
                                    <div class="flex items-center justify-end gap-space-md">
                                        <button type="button" @click="confirmOpen = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                                        <button
                                            type="button"
                                            wire:loading.attr="disabled"
                                            wire:target="submit"
                                            @click="confirmOpen = false; $wire.call('submit').then((ok) => { if (ok) { attemptOpen = false; } })"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                                        >
                                            {{ $latestAttempt ? 'Resubmit' : 'Submit' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endif
        @endif
    </div>

    @if (!$isStudent)
        <!-- Question List (teacher view, read-only) -->
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
            <h2 class="font-label-lg text-label-lg text-on-surface">Questions</h2>
            <div class="divide-y divide-outline-variant">
                @foreach ($assessment->questions as $question)
                    <div class="space-y-space-md {{ $loop->first ? '' : 'pt-space-lg' }} {{ $loop->last ? '' : 'pb-space-lg' }}">
                        <p class="text-body-xs text-on-surface-variant mb-space-sm">Question {{ $loop->iteration }} &middot; {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts</p>
                        <div class="rte-content prose prose-sm max-w-none text-on-surface">{!! $question->description !!}</div>
                        @if ($question->files->isNotEmpty())
                            <div class="mt-space-md space-y-space-xs">
                                @foreach ($question->files as $file)
                                    <div class="flex items-center gap-space-xs text-body-sm text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[16px]">description</span>
                                        {{ $file->title }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Answer Attempts Section -->
    @if ($isStudent && $attemptRows->isNotEmpty())
        <div class="space-y-space-md">
            <h2 class="font-label-lg text-label-lg text-on-surface">Answer Attempts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                @foreach ($attemptRows->reverse() as $row)
                    <div
                        class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md cursor-pointer hover:bg-surface-container/50 transition"
                        x-data="{ open: false }"
                        @click="open = true"
                    >
                        <div class="flex items-start justify-between gap-space-md">
                            <div class="flex-1">
                                <div class="flex items-center gap-space-sm mb-space-xs">
                                    <h3 class="font-label-lg text-label-lg text-on-surface">Attempt {{ $row['attempt']->attempt_number }}</h3>
                                </div>
                                @if ($row['attempt']->submitter)
                                    <div class="flex items-center gap-space-sm">
                                        <x-avatar :user="$row['attempt']->submitter" size="10" />
                                        <div>
                                            <p class="text-body-sm text-on-surface-variant">Submitted by <span class="font-medium text-on-surface">{{ $row['attempt']->submitter->name }}</span></p>
                                            <p class="text-body-xs text-on-surface-variant">
                                                {{ $row['attempt']->submitted_at ? $row['attempt']->submitted_at_display->format('M j, Y H:i') : 'Not submitted' }}
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-body-sm text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[16px] inline-block -mt-1 mr-space-xs">schedule</span>
                                        Not submitted
                                    </p>
                                @endif
                            </div>
                            @if ($row['score'])
                                <div class="bg-primary rounded-lg p-space-md text-on-primary text-center min-w-[140px] flex-shrink-0">
                                    <p class="text-body-xs opacity-90 mb-space-xs">SCORE</p>
                                    <p class="text-headline-sm font-bold">{{ rtrim(rtrim(number_format($row['score']->score, 1), '0'), '.') }} <span class="text-body-xs font-normal">pts</span></p>
                                    <p class="text-body-xs opacity-75 mt-space-xs">{{ $row['score']->graded_at_display?->format('j M Y, H:i') ?? '—' }}</p>
                                </div>
                            @elseif ($latestAttempt && $row['attempt']->id === $latestAttempt->id)
                                <div class="bg-surface-container rounded-lg p-space-md text-on-surface-variant text-center min-w-[140px] flex-shrink-0">
                                    <p class="text-body-xs font-medium">Awaiting Grade</p>
                                </div>
                            @endif
                        </div>

                        <template x-teleport="body">
                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-[100] bg-surface flex flex-col"
                            >
                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-200 delay-75"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    class="flex-1 overflow-y-auto p-space-lg space-y-space-lg"
                                >
                                    <div class="flex items-center justify-between border-b border-outline-variant pb-space-md">
                                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Attempt {{ $row['attempt']->attempt_number }} Answer</h2>
                                        <button type="button" @click="open = false" class="p-2 hover:bg-surface-container rounded transition">
                                            <span class="material-symbols-outlined text-on-surface-variant">close</span>
                                        </button>
                                    </div>

                                    @if ($row['answer']?->answer_text)
                                        <div class="rte-content prose prose-sm max-w-none text-on-surface">
                                            {!! $row['answer']->answer_text !!}
                                        </div>
                                    @else
                                        <p class="text-body-sm text-on-surface-variant">No answer submitted.</p>
                                    @endif

                                    @if ($row['score']?->feedback)
                                        <div class="pt-space-md border-t border-outline-variant">
                                            <p class="text-body-xs text-on-surface-variant mb-space-xs">Feedback</p>
                                            <p class="text-body-sm text-on-surface">{{ $row['score']->feedback }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </template>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Teacher: Manage Exam Questions (proctored exams only) -->
    @if (!$isStudent && $isProctored && $canGrade)
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg flex items-center justify-between gap-space-md">
            <div>
                <p class="font-label-md text-label-md text-on-surface">Exam Questions</p>
                <p class="text-body-sm text-on-surface-variant mt-space-xs">
                    {{ $assessment->quiz && $assessment->quiz->questions->isNotEmpty() ? $assessment->quiz->questions->count().' question(s) configured.' : 'No questions configured yet — students cannot start this exam until questions are added.' }}
                </p>
            </div>
            <a
                href="{{ route('assessments.final-exam.questions.edit', $assessment) }}"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity flex-shrink-0"
            >
                Manage Questions
            </a>
        </div>
    @endif

    <!-- Teacher: Submissions List -->
    @if (!$isStudent)
        <div class="space-y-space-md">
            <h2 class="font-label-lg text-label-lg text-on-surface">Student Submissions</h2>
            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
                @forelse ($studentRows as $row)
                    <div wire:key="student-{{ $row['user']->id }}" class="p-space-lg">
                        <div class="flex items-center justify-between gap-space-md mb-space-md">
                            <div class="flex-1 min-w-0">
                                <p class="font-label-md text-label-md text-on-surface">{{ $row['user']->name }}</p>
                                <p class="text-body-sm text-on-surface-variant mt-1">
                                    @if ($row['attempt'])
                                        Attempt {{ $row['attempt']->attempt_number }} &middot; submitted {{ $row['attempt']->submitted_at_display?->format('M j, Y H:i') }}
                                    @else
                                        Not submitted
                                    @endif
                                </p>
                            </div>

                            <span class="inline-flex items-center px-space-md py-space-xs rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant flex-shrink-0">
                                {{ $row['score'] ? 'Score: '.rtrim(rtrim(number_format($row['score']->score, 2), '0'), '.') : ($row['attempt'] ? 'Ungraded' : 'Not submitted') }}
                            </span>

                            @if ($canGrade && $row['attempt'])
                                <button
                                    type="button"
                                    wire:click="openGrading('{{ $row['user']->id }}')"
                                    class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition flex-shrink-0"
                                >
                                    Grade
                                </button>
                            @endif
                        </div>

                        @if ($isProctored && $row['proctorSession'])
                            <div class="mt-space-md pt-space-md border-t border-outline-variant space-y-space-md" x-data="{ reviewOpen: false }">
                                <div class="flex items-center justify-between gap-space-md">
                                    <div class="flex items-center gap-space-sm text-body-sm">
                                        <span class="material-symbols-outlined text-[16px]" :class="{}">shield</span>
                                        <span class="text-on-surface-variant">Proctoring:</span>
                                        <span class="font-medium text-on-surface">{{ str($row['proctorSession']->status->value)->title() }}</span>
                                        <span class="text-on-surface-variant">&middot; {{ $row['proctorSession']->events->count() }} event(s)</span>
                                        @if ($row['proctorSession']->review_decision)
                                            <span class="inline-flex items-center px-space-sm py-0.5 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">
                                                Review: {{ str($row['proctorSession']->review_decision->value)->replace('_', ' ')->title() }}
                                            </span>
                                        @endif
                                    </div>
                                    <button type="button" @click="reviewOpen = !reviewOpen" class="px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition flex-shrink-0">
                                        Review
                                    </button>
                                </div>

                                <div x-show="reviewOpen" x-cloak class="space-y-space-sm">
                                    @if ($row['proctorSession']->events->isNotEmpty())
                                        <ul class="text-body-xs text-on-surface-variant space-y-1 max-h-40 overflow-y-auto">
                                            @foreach ($row['proctorSession']->events as $event)
                                                <li>{{ $event->detected_at_display?->format('H:i:s') ?? $event->detected_at }} &middot; {{ str($event->event_type->value)->replace('_', ' ')->title() }} ({{ $event->severity->value }})</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <div class="flex items-end gap-space-sm">
                                        <div class="flex-1">
                                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Decision</label>
                                            <select wire:model="reviewDecision.{{ $row['proctorSession']->id }}" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-sm text-body-sm">
                                                <option value="no_action" @selected(($row['proctorSession']->review_decision?->value ?? 'no_action') === 'no_action')>No Action</option>
                                                <option value="warning" @selected($row['proctorSession']->review_decision?->value === 'warning')>Warning</option>
                                                <option value="disqualified" @selected($row['proctorSession']->review_decision?->value === 'disqualified')>Disqualified</option>
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Notes</label>
                                            <input type="text" wire:model="reviewNotes.{{ $row['proctorSession']->id }}" value="{{ $row['proctorSession']->review_notes }}" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-sm text-body-sm" />
                                        </div>
                                        <button type="button" wire:click="reviewProctorSession('{{ $row['proctorSession']->id }}')" class="px-space-md py-space-sm bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity flex-shrink-0">
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($gradingUserId === $row['user']->id)
                            <div class="mt-space-md pt-space-md border-t border-outline-variant space-y-space-md">
                                <div class="rte-content text-body-sm text-on-surface-variant">{!! $row['answer']?->answer_text !!}</div>

                                <form wire:submit="submitGrade" class="space-y-space-md">
                                    <div class="space-y-space-md">
                                        <h4 class="font-label-md text-label-md text-on-surface">Question Scores</h4>
                                        @foreach ($assessment->questions as $question)
                                            <div>
                                                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">
                                                    Question {{ $loop->iteration }} ({{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts)
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $question->points }}"
                                                    wire:model="gradeQuestionScores.{{ $question->id }}"
                                                    class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                                                />
                                                @error("gradeQuestionScores.{$question->id}") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                            </div>
                                        @endforeach
                                    </div>

                                    <div>
                                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Feedback</label>
                                        <textarea wire:model="gradeFeedback" rows="3" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                                    </div>

                                    <div class="flex gap-space-md">
                                        <button type="button" wire:click="cancelGrading" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                                            Cancel
                                        </button>
                                        <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                                            Save Grade
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-space-lg text-center text-body-sm text-on-surface-variant">No students enrolled.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
