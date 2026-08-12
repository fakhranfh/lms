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
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h1 class="font-headline-md text-headline-md text-on-surface">{{ $assessment->title }}</h1>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-space-lg border-t border-b border-outline-variant py-space-lg">
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Total Questions</p>
                <p class="text-body-sm text-on-surface font-medium">{{ $quiz->questions->count() }}</p>
            </div>
            <div>
                <p class="text-body-xs text-on-surface-variant mb-space-xs uppercase tracking-wide">Total Attempts</p>
                <p class="text-body-sm text-on-surface font-medium">
                    {{ $quiz->total_attempts ?? 'Unlimited' }}
                </p>
            </div>
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

        @if ($isStudent)
            @if ($inProgress)
                <div x-data="{}">
                    <h2 class="font-label-lg text-label-lg text-on-surface mb-space-md">Attempt {{ $inProgress->attempt_number }} — In Progress</h2>

                    <form wire:submit="submitAttempt" class="space-y-space-lg">
                        @foreach ($quiz->questions as $question)
                            <div class="border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                                <p class="font-label-md text-label-md text-on-surface">
                                    Question {{ $loop->iteration }} &middot; {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts
                                </p>
                                <p class="text-body-md text-on-surface">{{ $question->description }}</p>

                                @if (in_array($question->question_type->value, ['multiple_choice', 'true_false']))
                                    <div class="space-y-space-xs">
                                        @foreach ($question->options as $option)
                                            <label class="flex items-center gap-space-sm text-body-sm text-on-surface cursor-pointer">
                                                <input type="radio" name="answer-{{ $question->id }}" wire:model="answers.{{ $question->id }}" value="{{ $option->id }}" />
                                                {{ $option->label }}
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <textarea wire:model="answers.{{ $question->id }}" rows="4" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                                @endif
                            </div>
                        @endforeach

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="submitAttempt"
                            class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                        >
                            <span wire:loading wire:target="submitAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                            Submit Quiz
                        </button>
                    </form>
                </div>
            @elseif ($canStart)
                <button
                    type="button"
                    wire:click="startAttempt"
                    wire:loading.attr="disabled"
                    wire:target="startAttempt"
                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
                >
                    <span wire:loading wire:target="startAttempt" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                    Start Attempt
                </button>
            @elseif ($isExpired)
                <p class="text-body-sm text-on-surface-variant">The submission window for this quiz has closed.</p>
            @else
                <p class="text-body-sm text-on-surface-variant">You have reached the maximum number of attempts for this quiz.</p>
            @endif
        @endif
    </div>

    @if ($isStudent && $attemptRows->isNotEmpty())
        <div class="space-y-space-md">
            <h2 class="font-label-lg text-label-lg text-on-surface">Attempt History</h2>
            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
                @foreach ($attemptRows as $row)
                    <div class="p-space-lg flex items-center justify-between gap-space-md">
                        <div>
                            <p class="font-label-md text-label-md text-on-surface">Attempt {{ $row['attempt']->attempt_number }}</p>
                            <p class="text-body-sm text-on-surface-variant">Submitted {{ $row['attempt']->submitted_at?->format('M j, Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-label-md text-label-md text-on-surface">{{ rtrim(rtrim(number_format($row['total'], 1), '0'), '.') }} pts</p>
                            @if ($row['pending'])
                                <p class="text-body-xs text-on-surface-variant">Pending manual grading</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
                                        {{ $row['attemptCount'] }} attempt(s) &middot; submitted {{ $row['attempt']->submitted_at?->format('M j, Y H:i') }}
                                    @else
                                        Not attempted
                                    @endif
                                </p>
                            </div>

                            <span class="inline-flex items-center px-space-md py-space-xs rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant flex-shrink-0">
                                {{ $row['score'] ? 'Score: '.rtrim(rtrim(number_format($row['score']->score, 2), '0'), '.') : ($row['attempt'] ? 'Ungraded' : 'Not submitted') }}
                                @if ($row['pending']) &middot; Needs grading @endif
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
                                <form wire:submit="submitGrade" class="space-y-space-md">
                                    @foreach ($quiz->questions as $question)
                                        @if (in_array($question->question_type->value, ['short_answer', 'essay']))
                                            <div>
                                                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">
                                                    Question {{ $loop->iteration }} ({{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts)
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $question->points }}"
                                                    wire:model="gradeScores.{{ $question->id }}"
                                                    class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                                                />
                                                @error("gradeScores.{$question->id}") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                            </div>
                                        @endif
                                    @endforeach

                                    <div class="flex gap-space-md">
                                        <button type="button" wire:click="cancelGrading" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                                            Cancel
                                        </button>
                                        <button type="submit" wire:loading.attr="disabled" wire:target="submitGrade" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50">
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
