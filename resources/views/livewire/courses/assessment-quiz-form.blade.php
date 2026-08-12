@section('title', $pageTitle)

<div class="max-w-3xl space-y-space-lg">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Back to Assessments
            </a>
            <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $pageTitle }}</h1>
        </div>

        <a href="{{ route('quiz-instructions.edit') }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">info</span>
            {{ $hasInstructions ? 'Edit Global Instructions' : 'Set Global Instructions' }}
        </a>
    </div>

    <form wire:submit="save" class="space-y-space-lg">
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
            <div>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Title</label>
                <input type="text" wire:model="title" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                @error('title') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-space-md">
                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Weight (%)</label>
                    <input type="number" step="0.01" min="0" max="100" wire:model="weight" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    @error('weight') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Status</label>
                    <select wire:model="status" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ str($statusOption->value)->title() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Session</label>
                <select wire:model="sessionId" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                    <option value="">Select a session</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->title }}</option>
                    @endforeach
                </select>
                <p class="text-body-xs text-on-surface-variant mt-space-xs">The quiz's availability window follows the selected session's dates.</p>
                @error('sessionId') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-3 gap-space-md">
                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Total Attempts</label>
                    <input type="number" min="1" step="1" wire:model="totalAttempts" placeholder="Unlimited" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    @error('totalAttempts') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Scoring Method</label>
                    <select wire:model="scoringMethod" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50">
                        @foreach ($scoringMethods as $method)
                            <option value="{{ $method->value }}">{{ str($method->value)->title() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Time Limit (minutes)</label>
                    <input type="number" min="1" step="1" wire:model="timeLimitPerAttempt" placeholder="Unlimited" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    @error('timeLimitPerAttempt') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Questions -->
        <div class="space-y-space-md">
            <div class="flex items-center justify-between">
                <h2 class="font-label-lg text-label-lg text-on-surface">Questions</h2>
                <button type="button" wire:click="addQuestion" class="text-primary text-body-sm font-medium hover:underline inline-flex items-center gap-space-xs">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Add Question
                </button>
            </div>
            @error('questions') <p class="text-body-xs text-error">{{ $message }}</p> @enderror

            @foreach ($questions as $index => $question)
                <div wire:key="question-{{ $index }}" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <p class="font-label-md text-label-md text-on-surface">Question {{ $index + 1 }}</p>
                        @if (count($questions) > 1)
                            <button type="button" wire:click="removeQuestion({{ $index }})" class="text-error text-body-sm hover:underline">Remove</button>
                        @endif
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                        <textarea wire:model="questions.{{ $index }}.description" rows="2" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                        @error("questions.{$index}.description") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Points</label>
                        <input type="number" step="0.01" min="0" wire:model="questions.{{ $index }}.points" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                        @error("questions.{$index}.points") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    @error("questions.{$index}.options") <p class="text-body-xs text-error">{{ $message }}</p> @enderror

                    <div class="space-y-space-sm">
                        <label class="block font-label-sm text-label-sm text-secondary">Options (select the correct one)</label>
                        @foreach ($question['options'] as $optionIndex => $option)
                            <div wire:key="question-{{ $index }}-option-{{ $optionIndex }}" class="flex items-center gap-space-sm">
                                <input
                                    type="radio"
                                    name="correct-option-{{ $index }}"
                                    wire:click="toggleCorrect({{ $index }}, {{ $optionIndex }})"
                                    @checked($option['isCorrect'])
                                />
                                <input
                                    type="text"
                                    wire:model="questions.{{ $index }}.options.{{ $optionIndex }}.label"
                                    placeholder="Option label"
                                    class="flex-1 px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                                />
                                @if (count($question['options']) > 2)
                                    <button type="button" wire:click="removeOption({{ $index }}, {{ $optionIndex }})" class="text-error text-body-sm hover:underline">Remove</button>
                                @endif
                            </div>
                        @endforeach

                        <button type="button" wire:click="addOption({{ $index }})" class="text-primary text-body-sm font-medium hover:underline">
                            Add Option
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex gap-space-md">
            <a href="{{ route('assessments.index', $course) }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                Cancel
            </a>
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
            >
                <span wire:loading wire:target="save" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                Save
            </button>
        </div>
    </form>
</div>
