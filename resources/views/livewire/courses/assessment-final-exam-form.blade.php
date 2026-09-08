@section('title', $pageTitle)

<div class="w-full space-y-space-lg">
    <div class="flex items-start justify-between gap-space-md">
        <div>
            <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Back to Assessments
            </a>
            <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $pageTitle }}</h1>
        </div>

        @if ($showUrl)
            <a
                href="{{ $showUrl }}"
                class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition flex-shrink-0"
            >
                Show Exam
            </a>
        @endif
    </div>

    @if (app()->isLocal())
        <div class="bg-tertiary-container border border-outline-variant rounded-lg p-space-md flex items-center justify-between">
            <p class="font-body-sm text-body-sm text-on-tertiary-container">Dev tools</p>
            <div class="flex items-center gap-space-xs">
                @foreach ($examTypes as $examTypeOption)
                    <button
                        type="button"
                        wire:click="devAutofill('{{ $examTypeOption->value }}')"
                        wire:loading.attr="disabled"
                        wire:target="devAutofill"
                        class="px-space-md py-space-xs bg-tertiary text-on-tertiary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-xs"
                    >
                        <span wire:loading wire:target="devAutofill('{{ $examTypeOption->value }}')" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                        Autofill {{ str($examTypeOption->value)->replace('_', ' ')->title() }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <form wire:submit="save" class="w-full space-y-space-lg">
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

            <div class="grid grid-cols-2 gap-space-md">
                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Start Date</label>
                    <input type="datetime-local" wire:model="startDate" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    @error('startDate') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">End Date</label>
                    <input type="datetime-local" wire:model="endDate" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    @error('endDate') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Exam Type</label>
                <div class="flex items-center gap-space-lg py-space-sm">
                    @foreach ($examTypes as $examTypeOption)
                        <label class="inline-flex items-center gap-space-xs cursor-pointer">
                            <input
                                type="radio"
                                wire:model="examType"
                                value="{{ $examTypeOption->value }}"
                                class="w-4 h-4 text-primary border-outline focus:ring-primary/50"
                                @change="window.toggleFinalExamType($wire, $el)"
                            />
                            <span class="font-body-md text-body-md text-on-surface">{{ str($examTypeOption->value)->replace('_', ' ')->title() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('examType') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Instructions</label>
                <p class="text-body-xs text-on-surface-variant mb-space-xs">Shown to students before they start this exam. Tailor it to this exam type (e.g. what materials are allowed).</p>
                <x-rich-text-editor id="final-exam-instructions" wire-model="instructions" :value="$instructions" />
                @error('instructions') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Questions -->
        <div class="space-y-space-md">
            <div class="flex items-center justify-between">
                <h2 class="font-label-lg text-label-lg text-on-surface font-bold">Questions</h2>
                <button
                    type="button"
                    data-take-home-hide
                    @click="window.addSyllabusRow($wire, 'question-template', 'questions', { id: null, description: '', questionType: 'essay', points: '', options: [] })"
                    class="text-primary text-body-sm font-medium hover:underline inline-flex items-center gap-space-xs {{ $examType === 'take_home' ? 'hidden' : '' }}"
                >
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Add Question
                </button>
            </div>
            <p data-take-home-only class="text-body-xs text-on-surface-variant {{ $examType === 'take_home' ? '' : 'hidden' }}">Take-home exams are a single essay prompt.</p>
            @error('questions') <p class="text-body-xs text-error">{{ $message }}</p> @enderror

            @foreach ($questions as $index => $question)
                @continue($question === null)
                <div wire:key="question-{{ $index }}" data-row data-question-row class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <p data-take-home-hide class="font-label-md text-label-md text-on-surface {{ $examType === 'take_home' ? 'hidden' : '' }}">Question {{ $index + 1 }}</p>
                        @if (count($questions) > 1)
                            <button type="button" data-take-home-hide @click="window.removeSyllabusRow($wire, 'questions.{{ $index }}', $el)" class="text-error text-body-sm hover:underline {{ $examType === 'take_home' ? 'hidden' : '' }}">Remove</button>
                        @endif
                    </div>

                    <div data-take-home-hide class="{{ $examType === 'take_home' ? 'hidden' : '' }}">
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Question Type</label>
                        <div class="flex items-center gap-space-lg py-space-sm">
                            @foreach ($questionTypes as $questionTypeOption)
                                <label class="inline-flex items-center gap-space-xs cursor-pointer">
                                    <input
                                        type="radio"
                                        wire:model="questions.{{ $index }}.questionType"
                                        value="{{ $questionTypeOption->value }}"
                                        class="w-4 h-4 text-primary border-outline focus:ring-primary/50"
                                        @change="window.toggleFinalExamQuestionType($wire, {{ $index }}, $el)"
                                    />
                                    <span class="font-body-md text-body-md text-on-surface">{{ str($questionTypeOption->value)->replace('_', ' ')->title() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error("questions.{$index}.questionType") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                        <x-rich-text-editor id="question-{{ $index }}" wire-model="questions.{{ $index }}.description" :value="$question['description']" :allow-audio="true" />
                        @error("questions.{$index}.description") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    <div data-mc-fields data-field="options" class="{{ $question['questionType'] === \App\Enums\AssessmentQuestionType::MultipleChoice->value ? '' : 'hidden' }}" @error("questions.{$index}.options") data-field-error @enderror>
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
                        @error("questions.{$index}.options") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>

                    <div data-essay-fields class="w-40 {{ $question['questionType'] === \App\Enums\AssessmentQuestionType::MultipleChoice->value ? 'hidden' : '' }}">
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Points</label>
                        <input type="number" step="0.01" min="0" wire:model="questions.{{ $index }}.points" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                        @error("questions.{$index}.points") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach

            <template id="question-template">
                <div data-row data-question-row class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <p class="font-label-md text-label-md text-on-surface">New question</p>
                        <button type="button" @click="window.removeSyllabusRow($wire, 'questions.__NEW__', $el)" class="text-error text-body-sm hover:underline">Remove</button>
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Question Type</label>
                        <div class="flex items-center gap-space-lg py-space-sm">
                            @foreach ($questionTypes as $questionTypeOption)
                                <label class="inline-flex items-center gap-space-xs cursor-pointer">
                                    <input
                                        type="radio"
                                        name="question-type-__NEW__"
                                        value="{{ $questionTypeOption->value }}"
                                        @checked($questionTypeOption === \App\Enums\AssessmentQuestionType::Essay)
                                        class="w-4 h-4 text-primary border-outline focus:ring-primary/50"
                                        @change="$wire.set('questions.__NEW__.questionType', '{{ $questionTypeOption->value }}', false); window.toggleFinalExamQuestionType($wire, __NEW__, $el)"
                                    />
                                    <span class="font-body-md text-body-md text-on-surface">{{ str($questionTypeOption->value)->replace('_', ' ')->title() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Description</label>
                        <x-rich-text-editor id="question-__NEW__" wire-model="questions.__NEW__.description" :allow-audio="true" />
                    </div>

                    <div data-mc-fields data-field="options" class="hidden">
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
                    </div>

                    <div data-essay-fields class="w-40">
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">Points</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            @input="$wire.set('questions.__NEW__.points', $event.target.value, false)"
                            class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                        />
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
