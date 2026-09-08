@section('title', $pageTitle)

<div class="w-full space-y-space-lg">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Back to Assessments
            </a>
            <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $pageTitle }}</h1>
        </div>

        <div class="flex items-center gap-space-lg flex-shrink-0">
            <a href="{{ route('quiz-instructions.edit') }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
                <span class="material-symbols-outlined text-[16px]">info</span>
                {{ $hasInstructions ? 'Edit Global Instructions' : 'Set Global Instructions' }}
            </a>

            @if ($showUrl)
                <a
                    href="{{ $showUrl }}"
                    class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                >
                    Show Quiz
                </a>
            @endif
        </div>
    </div>

    @if (app()->isLocal())
        <div class="bg-tertiary-container border border-outline-variant rounded-lg p-space-md flex items-center justify-between">
            <p class="font-body-sm text-body-sm text-on-tertiary-container">Dev tools</p>
            <button
                type="button"
                wire:click="devAutofill"
                wire:loading.attr="disabled"
                wire:target="devAutofill"
                class="px-space-md py-space-xs bg-tertiary text-on-tertiary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-xs"
            >
                <span wire:loading wire:target="devAutofill" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                Autofill
            </button>
        </div>
    @endif

    <form
        x-data="{ submitting: false }"
        @submit.prevent="
            if (!window.validateQuizForm($el)) {
                $nextTick(() => window.scrollToFirstFormError($el));
                return;
            }
            submitting = true;
            $wire.save();
        "
        @assessmentquizform-error.window="submitting = false; $nextTick(() => window.scrollToFirstFormError($el))"
        class="w-full space-y-space-lg"
    >
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-lg">
            <div data-field="title" @error('title') data-field-error @enderror>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Title</label>
                <input type="text" wire:model="title" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50 @error('title') border-error ring-2 ring-error/30 @enderror" />
                <p class="text-body-xs text-error mt-space-xs hidden" data-js-error></p>
                @error('title') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-space-md">
                <div @error('weight') data-field-error @enderror>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Weight (%)</label>
                    <input type="number" step="0.01" min="0" max="100" wire:model="weight" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50 @error('weight') border-error ring-2 ring-error/30 @enderror" />
                    @error('weight') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Status</label>
                    <div class="flex items-center gap-space-lg py-space-sm">
                        @foreach ($statuses as $statusOption)
                            <label class="inline-flex items-center gap-space-xs cursor-pointer">
                                <input type="radio" wire:model="status" value="{{ $statusOption->value }}" class="w-4 h-4 text-primary border-outline focus:ring-primary/50" />
                                <span class="font-body-md text-body-md text-on-surface">{{ str($statusOption->value)->title() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div data-field="sessionId" @error('sessionId') data-field-error @enderror>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Session</label>
                <select wire:model="sessionId" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50 @error('sessionId') border-error ring-2 ring-error/30 @enderror">
                    <option value="">Select a session</option>
                    @foreach ($sessions as $index => $session)
                        <option value="{{ $session->id }}">Session {{ $index + 1 }} &mdash; {{ $session->title }}</option>
                    @endforeach
                </select>
                <p class="text-body-xs text-on-surface-variant mt-space-xs">The quiz's availability window follows the selected session's dates.</p>
                <p class="text-body-xs text-error mt-space-xs hidden" data-js-error></p>
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
                <button
                    type="button"
                    @click="window.addSyllabusRow($wire, 'question-template', 'questions', { id: null, description: '', options: [{ id: null, label: '', isCorrect: false }, { id: null, label: '', isCorrect: false }] })"
                    class="text-primary text-body-sm font-medium hover:underline inline-flex items-center gap-space-xs"
                >
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Add Question
                </button>
            </div>
            @error('questions') <p class="text-body-xs text-error">{{ $message }}</p> @enderror

            @foreach ($questions as $index => $question)
                @continue($question === null)
                <div wire:key="question-{{ $index }}" data-row data-question-row class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <p class="font-label-md text-label-md text-on-surface">Question {{ $index + 1 }}</p>
                        @if (count($questions) > 1)
                            <button type="button" @click="window.removeSyllabusRow($wire, 'questions.{{ $index }}', $el)" class="text-error text-body-sm hover:underline">Remove</button>
                        @endif
                    </div>

                    <div data-field="description" @error("questions.{$index}.description") data-field-error @enderror>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                        <div @error("questions.{$index}.description") class="rounded-lg ring-2 ring-error/30 border border-error" @enderror>
                            <x-rich-text-editor id="question-{{ $index }}" wire-model="questions.{{ $index }}.description" :value="$question['description']" :allow-audio="true" />
                        </div>
                        <p class="text-body-xs text-error mt-space-xs hidden" data-js-error></p>
                        @error("questions.{$index}.description") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    <div data-field="options" @error("questions.{$index}.options") data-field-error @enderror>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Options (select the correct one)</label>

                        <div data-options-container class="space-y-space-sm">
                            @foreach ($question['options'] as $optionIndex => $option)
                                <div wire:key="question-{{ $index }}-option-{{ $optionIndex }}" data-row data-option-row class="flex items-start gap-space-sm">
                                    <input
                                        type="radio"
                                        name="correct-option-{{ $index }}"
                                        data-option-correct
                                        class="mt-space-md"
                                        @change="window.setSyllabusRadio($wire, 'questions.{{ $index }}.options', {{ $optionIndex }})"
                                        @checked($option['isCorrect'])
                                    />
                                    <div class="flex-1">
                                        <x-rich-text-editor
                                            id="question-{{ $index }}-option-{{ $optionIndex }}"
                                            wire-model="questions.{{ $index }}.options.{{ $optionIndex }}.label"
                                            :value="$option['label']"
                                            :allow-links="false"
                                            :allow-audio="true"
                                        />
                                    </div>
                                    @if (count($question['options']) > 2)
                                        <button type="button" @click="window.removeSyllabusRow($wire, 'questions.{{ $index }}.options.{{ $optionIndex }}', $el)" class="text-error text-body-sm hover:underline">Remove</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <button
                            type="button"
                            @click="window.addQuizOption($wire, {{ $index }}, $el.closest('[data-question-row]').querySelector('[data-options-container]'))"
                            class="mt-space-sm text-primary text-body-sm font-medium hover:underline"
                        >
                            Add Option
                        </button>
                        <p class="text-body-xs text-error mt-space-xs hidden" data-js-error></p>
                        @error("questions.{$index}.options") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach

            <template id="question-template">
                <div data-row data-question-row class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <p class="font-label-md text-label-md text-on-surface">New question</p>
                        <button type="button" @click="window.removeSyllabusRow($wire, 'questions.__NEW__', $el)" class="text-error text-body-sm hover:underline">Remove</button>
                    </div>

                    <div data-field="description">
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                        <x-rich-text-editor id="question-__NEW__" wire-model="questions.__NEW__.description" :allow-audio="true" />
                        <p class="text-body-xs text-error mt-space-xs hidden" data-js-error></p>
                    </div>

                    <div data-field="options">
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Options (select the correct one)</label>

                        <div data-options-container class="space-y-space-sm">
                            <div data-row data-option-row class="flex items-start gap-space-sm">
                                <input type="radio" name="correct-option-__NEW__" data-option-correct class="mt-space-md" @change="window.setSyllabusRadio($wire, 'questions.__NEW__.options', 0)" />
                                <div class="flex-1">
                                    <x-rich-text-editor id="question-__NEW__-option-0" wire-model="questions.__NEW__.options.0.label" :allow-links="false" :allow-audio="true" />
                                </div>
                            </div>
                            <div data-row data-option-row class="flex items-start gap-space-sm">
                                <input type="radio" name="correct-option-__NEW__" data-option-correct class="mt-space-md" @change="window.setSyllabusRadio($wire, 'questions.__NEW__.options', 1)" />
                                <div class="flex-1">
                                    <x-rich-text-editor id="question-__NEW__-option-1" wire-model="questions.__NEW__.options.1.label" :allow-links="false" :allow-audio="true" />
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="window.addQuizOption($wire, '__NEW__', $el.closest('[data-question-row]').querySelector('[data-options-container]'))"
                            class="mt-space-sm text-primary text-body-sm font-medium hover:underline"
                        >
                            Add Option
                        </button>
                        <p class="text-body-xs text-error mt-space-xs hidden" data-js-error></p>
                    </div>
                </div>
            </template>

            <template id="option-template">
                <div data-row data-option-row class="flex items-start gap-space-sm">
                    <input type="radio" name="correct-option-__QINDEX__" data-option-correct class="mt-space-md" />
                    <div class="flex-1">
                        <x-rich-text-editor id="question-__QINDEX__-option-__OPTINDEX__" wire-model="questions.__QINDEX__.options.__OPTINDEX__.label" :allow-links="false" :allow-audio="true" />
                    </div>
                    <button type="button" data-remove-option class="text-error text-body-sm hover:underline">Remove</button>
                </div>
            </template>
        </div>

        <div class="h-16" aria-hidden="true"></div>

        <div class="relative sticky bottom-[-1.5rem] z-20 -mx-gutter px-gutter pt-space-md pb-space-lg bg-surface border-t border-outline-variant flex gap-space-md before:content-[''] before:absolute before:left-0 before:right-0 before:-top-space-lg before:h-space-lg before:bg-surface before:-z-10">
            <a href="{{ route('assessments.index', $course) }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                Cancel
            </a>
            <button
                type="submit"
                :disabled="submitting"
                wire:loading.attr="disabled"
                wire:target="save"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm"
            >
                <span x-show="submitting" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                Save
            </button>
        </div>
    </form>
</div>
