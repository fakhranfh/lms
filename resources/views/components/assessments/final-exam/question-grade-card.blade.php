{{--
    Molecule: one question's grading row on the final exam grade page.
    Multiple-choice questions are already auto-graded at submission time,
    so they render read-only with their auto-graded score; only essay
    questions expose a score input bound to `gradeQuestionScores.<id>`
    on the enclosing Livewire component.
--}}
@props(['question', 'number', 'questionAnswer'])

<div>
    <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">
        Question {{ $number }} ({{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts)
    </label>
    <div class="rte-content prose prose-sm max-w-none text-on-surface-variant mb-space-xs">{!! $question->description !!}</div>

    @if ($question->question_type->value === 'multiple_choice')
        @php($correctOption = $question->options->firstWhere('is_correct', true))
        @php($selectedOption = $questionAnswer ? $question->options->firstWhere('id', $questionAnswer->selected_option_id) : null)
        @php($isCorrect = $selectedOption && $correctOption && $selectedOption->id === $correctOption->id)

        <div class="mb-space-sm">
            @if (! $selectedOption)
                <p class="text-body-sm text-on-surface-variant mb-space-xs">{{ __('No answer submitted.') }}</p>
            @endif

            <x-assessments.final-exam.question-options-list :question="$question" :selected-option-id="$questionAnswer?->selected_option_id" />
        </div>

        @if ($selectedOption)
            <span class="inline-flex items-center gap-space-xs px-space-sm py-0.5 rounded-full text-body-xs font-medium {{ $isCorrect ? 'bg-success/10 text-success' : 'bg-error/10 text-error' }}">
                <span class="material-symbols-outlined text-[14px]">{{ $isCorrect ? 'check_circle' : 'cancel' }}</span>
                {{ $isCorrect ? __('Correct') : __('Incorrect') }}
            </span>
        @endif
    @else
        @if ($questionAnswer)
            <div class="rte-content text-body-sm text-on-surface-variant mb-space-xs">{!! $questionAnswer->answer_text ?? __('No answer submitted.') !!}</div>
        @endif
        <input
            type="number"
            step="0.01"
            min="0"
            max="{{ $question->points }}"
            wire:model="gradeQuestionScores.{{ $question->id }}"
            class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
        />
        @error("gradeQuestionScores.{$question->id}") <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
    @endif
</div>
