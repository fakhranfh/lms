@section('title', $assessment->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div>
        <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Assessments
        </a>
        <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $assessment->title }}</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">
            Personal Assignment &middot; Weight {{ rtrim(rtrim(number_format($assessment->weight, 2), '0'), '.') }}%
            @if ($assessment->start_date)
                &middot; {{ $assessment->start_date->format('M j, Y') }} &ndash; {{ $assessment->end_date?->format('M j, Y') }}
            @endif
        </p>
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

    <!-- Questions -->
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
        <h2 class="font-label-lg text-label-lg text-on-surface">Questions</h2>
        @foreach ($assessment->questions as $question)
            <div class="space-y-space-xs">
                <p class="text-body-xs text-on-surface-variant">Question {{ $loop->iteration }} &middot; {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts</p>
                <div class="prose prose-sm max-w-none text-on-surface">{!! $question->description !!}</div>
                @if ($question->files->isNotEmpty())
                    <ul class="space-y-1">
                        @foreach ($question->files as $file)
                            <li class="text-body-sm text-on-surface-variant flex items-center gap-space-xs">
                                <span class="material-symbols-outlined text-[16px]">description</span>
                                {{ $file->title }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    @if ($isStudent)
        <!-- Existing submission -->
        @if ($latestAttempt)
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                <div class="flex items-center justify-between">
                    <h2 class="font-label-lg text-label-lg text-on-surface">Your Submission (Attempt {{ $latestAttempt->attempt_number }})</h2>
                    @if ($latestScore)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-success/10 text-success">
                            Score: {{ rtrim(rtrim(number_format($latestScore->score, 2), '0'), '.') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">
                            Awaiting grade
                        </span>
                    @endif
                </div>
                <div class="prose prose-sm max-w-none text-on-surface">{!! $latestAnswer?->answer_text !!}</div>
                @if ($latestScore?->feedback)
                    <div class="pt-space-md border-t border-outline-variant">
                        <p class="font-label-sm text-label-sm text-secondary mb-space-xs">Feedback</p>
                        <p class="text-body-sm text-on-surface">{{ $latestScore->feedback }}</p>
                    </div>
                @endif
            </div>
        @endif

        @if ($canSubmit && $canResubmit)
            <form wire:submit="submit" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                <h2 class="font-label-lg text-label-lg text-on-surface">{{ $latestAttempt ? 'Resubmit' : 'Submit' }} Answer</h2>
                <div>
                    <x-rich-text-editor id="answer" wire-model="answerText" :value="$answerText" />
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
        @elseif (! $latestAttempt)
            <p class="text-body-sm text-on-surface-variant">The submission window is closed.</p>
        @endif
    @else
        <!-- Teacher: submissions list -->
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
            @forelse ($studentRows as $row)
                <div wire:key="student-{{ $row['user']->id }}" class="p-space-lg">
                    <div class="flex items-center justify-between gap-space-md">
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

                        <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant flex-shrink-0">
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
                                <div class="grid grid-cols-2 gap-space-md">
                                    <div>
                                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Score</label>
                                        <input type="number" step="0.01" min="0" wire:model="gradeScore" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                                        @error('gradeScore') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                    </div>
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
    @endif
</div>
