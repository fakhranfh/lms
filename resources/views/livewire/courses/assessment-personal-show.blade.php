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
                        {{ $assessment->start_date->format('M j, Y, H:i') }}
                    @else
                        <span class="text-on-surface-variant">—</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Due</p>
                <p class="text-body-sm text-on-surface font-medium">
                    @if ($assessment->end_date)
                        {{ $assessment->end_date->format('M j, Y, H:i') }}
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
        </div>

        <!-- Latest Score Card -->
        @if ($isStudent && $latestScore)
            <div class="bg-gradient-to-br from-primary/90 to-primary rounded-lg p-space-lg text-on-primary space-y-space-md">
                <div>
                    <p class="text-body-sm opacity-90 mb-space-sm">Latest Score</p>
                    <p class="text-headline-lg font-bold">{{ rtrim(rtrim(number_format($latestScore->score, 1), '0'), '.') }} <span class="text-body-md font-normal">pts</span></p>
                    <p class="text-body-xs opacity-75 mt-space-md">Score Updated On: {{ $latestScore->graded_at?->format('j M Y, H:i') ?? '—' }}</p>
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
                    <button
                        type="button"
                        wire:click="openAttemptForViewing('{{ $latestAttempt->id }}')"
                        class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition flex-shrink-0"
                    >
                        View Attempt
                    </button>
                </div>
            @elseif ($isExpired)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">The submission window for this assessment has closed.</p>
                    </div>
                    @if ($latestAttempt)
                        <button
                            type="button"
                            wire:click="openAttemptForViewing('{{ $latestAttempt->id }}')"
                            class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition flex-shrink-0"
                        >
                            View Attempt
                        </button>
                    @endif
                </div>
            @elseif ($attemptLimit && $attemptsUsed >= $attemptLimit)
                <div class="flex items-center justify-between gap-space-md">
                    <div class="flex-1">
                        <p class="text-body-sm text-on-surface-variant">You have reached the maximum number of attempts for this assessment.</p>
                    </div>
                    @if ($latestAttempt)
                        <button
                            type="button"
                            wire:click="openAttemptForViewing('{{ $latestAttempt->id }}')"
                            class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition flex-shrink-0"
                        >
                            View Attempt
                        </button>
                    @endif
                </div>
            @elseif ($canSubmit && $canResubmit)
                <div>
                    @if ($latestAttempt)
                        <button
                            type="button"
                            wire:click="openAttemptForSubmission"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Continue Attempt {{ $latestAttempt->attempt_number + 1 }}
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="openAttemptForSubmission"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Start Attempt
                        </button>
                    @endif
                </div>
            @endif
        @endif
    </div>

    <!-- Answer Attempts Section -->
    @if ($isStudent && $attemptRows->isNotEmpty())
        <div class="space-y-space-md">
            <h2 class="font-label-lg text-label-lg text-on-surface">Answer Attempts</h2>
            <div class="grid gap-space-md">
                @foreach ($attemptRows as $row)
                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                        <div class="flex items-start justify-between gap-space-md">
                            <div class="flex-1">
                                <div class="flex items-center gap-space-md mb-space-md">
                                    <h3 class="font-label-lg text-label-lg text-on-surface">Attempt {{ $row['attempt']->attempt_number }}</h3>
                                    @if ($row['attempt']->id === $viewingAttemptId)
                                        <span class="text-body-xs text-primary font-medium">Viewing</span>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="openAttemptForViewing('{{ $row['attempt']->id }}')"
                                            class="p-2 hover:bg-surface-container rounded transition text-on-surface-variant"
                                            title="View attempt details"
                                        >
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    @endif
                                </div>
                                <p class="text-body-sm text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[16px] inline-block -mt-1 mr-space-xs">{{ $row['attempt']->submitted_by ? 'account_circle' : 'schedule' }}</span>
                                    @if ($row['attempt']->submitted_at)
                                        Submitted {{ $row['attempt']->submitted_at->format('M j, Y H:i') }}
                                    @else
                                        Not submitted
                                    @endif
                                </p>
                            </div>
                            @if ($row['score'])
                                <div class="bg-gradient-to-br from-primary/90 to-primary rounded-lg p-space-md text-on-primary text-center min-w-[140px] flex-shrink-0">
                                    <p class="text-body-xs opacity-90 mb-space-xs">SCORE</p>
                                    <p class="text-headline-sm font-bold">{{ rtrim(rtrim(number_format($row['score']->score, 1), '0'), '.') }} <span class="text-body-xs font-normal">pts</span></p>
                                    <p class="text-body-xs opacity-75 mt-space-xs">{{ $row['score']->graded_at?->format('j M y H:i') ?? '—' }}</p>
                                </div>
                            @else
                                <div class="bg-surface-container rounded-lg p-space-md text-on-surface-variant text-center min-w-[140px] flex-shrink-0">
                                    <p class="text-body-xs font-medium">Awaiting Grade</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
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
                                        Attempt {{ $row['attempt']->attempt_number }} &middot; submitted {{ $row['attempt']->submitted_at?->format('M j, Y H:i') }}
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

                        @if ($gradingUserId === $row['user']->id)
                            <div class="mt-space-md pt-space-md border-t border-outline-variant space-y-space-md">
                                <div class="text-body-sm text-on-surface-variant">{!! $row['answer']?->answer_text !!}</div>

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

    <!-- Attempt Detail Slide-Over Modal -->
    @if ($isStudent && $isModalOpen)
            <div class="fixed inset-0 z-50 bg-black/50 overflow-hidden" x-data="{ open: true }" x-show="open" x-cloak @click="open = false; $wire.call('closeAttemptDetail')">
                <div class="fixed inset-0 bg-surface flex flex-col" x-show="open" x-transition @click.stop>
                    <!-- Header -->
                    <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant">
                        <div>
                            @if ($viewingAttempt)
                                <h3 class="font-headline-sm text-headline-sm text-on-surface">Attempt {{ $viewingAttempt['attempt']->attempt_number }} - {{ $assessment->title }}</h3>
                                <p class="text-body-sm text-on-surface-variant mt-1">{{ $viewingAttempt['attempt']->submitted_at?->format('j M Y, H:i') ?? '—' }}</p>
                            @else
                                <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $latestAttempt ? 'Continue' : 'Start' }} Attempt - {{ $assessment->title }}</h3>
                            @endif
                        </div>
                        <button type="button" @click="open = false; $wire.call('closeAttemptDetail')" class="p-2 hover:bg-surface-container rounded transition">
                            <span class="material-symbols-outlined text-on-surface-variant">close</span>
                        </button>
                    </div>

                    <!-- Content Area -->
                    <div class="flex-1 overflow-hidden flex">
                        <!-- Left Sidebar: Score & Question List -->
                        <div class="w-64 border-r border-outline-variant overflow-y-auto p-space-lg space-y-space-lg">
                            @if ($viewingAttempt && $viewingAttempt['score'])
                                <div class="bg-gradient-to-br from-primary/90 to-primary rounded-lg p-space-md text-on-primary">
                                    <p class="text-body-xs opacity-90 mb-space-xs">SCORE</p>
                                    <p class="text-headline-sm font-bold">{{ rtrim(rtrim(number_format($viewingAttempt['score']->score, 1), '0'), '.') }} <span class="text-body-xs font-normal">pts</span></p>
                                    <p class="text-body-xs opacity-75 mt-space-xs">Last updated: {{ $viewingAttempt['score']->graded_at?->format('j M y H:i') ?? '—' }}</p>
                                </div>
                            @endif

                            <div>
                                <p class="font-label-sm text-label-sm text-on-surface-variant mb-space-md">QUESTION LIST</p>
                                @if ($viewingAttempt)
                                    <p class="text-body-xs text-on-surface-variant mb-space-md">Answered: {{ $assessment->questions->count() }} of {{ $assessment->questions->count() }}</p>
                                @endif
                                <div class="space-y-space-xs">
                                    @foreach ($assessment->questions as $question)
                                        <button type="button" class="w-full text-left p-space-sm rounded hover:bg-surface-container transition group">
                                            <div class="flex items-center justify-between">
                                                <span class="text-body-sm text-on-surface-variant group-hover:text-on-surface">Question {{ $loop->iteration }}</span>
                                                @if ($viewingAttempt)
                                                    <span class="inline-flex items-center px-space-xs py-1 rounded-full text-body-xs font-medium bg-primary/10 text-primary">
                                                        {{ $viewingAttempt['questionScores']->get($question->id)?->score ?? '—' }} of {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Middle: Questions & Answers -->
                        <div class="flex-1 overflow-y-auto p-space-lg">
                            <div class="space-y-space-lg">
                                @foreach ($assessment->questions as $question)
                                    <div class="space-y-space-md">
                                        <div>
                                            <p class="text-body-xs text-on-surface-variant mb-space-sm">Question {{ $loop->iteration }} &middot; {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts</p>
                                            <div class="prose prose-sm max-w-none text-on-surface">{!! $question->description !!}</div>
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
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Right Sidebar: Answer -->
                        <div class="w-80 border-l border-outline-variant overflow-y-auto p-space-lg space-y-space-lg">
                            @if ($viewingAttempt)
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant mb-space-md">ANSWER</p>

                                    @if ($viewingAttempt['answer']?->answerFile)
                                        <div class="p-space-md bg-surface-container rounded-lg mb-space-md">
                                            <div class="flex items-start gap-space-md">
                                                <span class="material-symbols-outlined text-primary text-[24px]">description</span>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-label-sm text-label-sm text-on-surface truncate">{{ $viewingAttempt['answer']->answerFile?->file_name ?? 'Attachment' }}</p>
                                                    <p class="text-body-xs text-on-surface-variant mt-1">{{ \Illuminate\Support\Number::fileSize($viewingAttempt['answer']->answerFile?->file_size ?? 0) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($viewingAttempt['answer']?->answer_text)
                                        <div class="prose prose-sm max-w-none text-on-surface">
                                            {!! $viewingAttempt['answer']->answer_text !!}
                                        </div>
                                    @endif
                                </div>

                                @if ($viewingAttempt['attempt']?->submitted_at)
                                    <div class="pt-space-md border-t border-outline-variant">
                                        <p class="text-body-xs text-on-surface-variant">Last saved {{ $viewingAttempt['attempt']->submitted_at->format('j M Y, H:i') }}</p>
                                    </div>
                                @endif
                            @else
                                <form wire:submit="submit" class="space-y-space-md">
                                    <div>
                                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-space-md">{{ $latestAttempt ? 'RESUBMIT ANSWER' : 'SUBMIT ANSWER' }}</label>
                                        <div>
                                            <x-rich-text-editor id="answer" wire-model="answerText" :value="$answerText" />
                                            @error('answerText') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div class="flex gap-space-md">
                                        <button
                                            type="button"
                                            @click="open = false; $wire.call('closeAttemptDetail')"
                                            class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            wire:loading.attr="disabled"
                                            wire:target="submit"
                                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50"
                                        >
                                            {{ $latestAttempt ? 'Resubmit' : 'Submit' }}
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
</div>
