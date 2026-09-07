{{--
    Molecule: one question's description and answer review in a quiz review
    modal. Relies on a `currentQuestion` Alpine property owned by the
    enclosing modal to toggle visibility. Questions are not individually
    weighted, so only the final score is shown (in the modal header) — not
    a per-question score.
--}}
@props(['question', 'index', 'total', 'answer'])

<div x-show="currentQuestion === {{ $index }}" x-cloak class="space-y-space-lg max-w-2xl mx-auto">
    <p class="text-body-sm text-on-surface-variant">Question {{ $index + 1 }} of {{ $total }}</p>
    <div class="rte-content prose prose-lg max-w-none text-on-surface">{!! $question->description !!}</div>

    @if (in_array($question->question_type->value, ['multiple_choice', 'true_false']))
        <div class="space-y-space-md">
            @foreach ($question->options as $option)
                <x-assessments.quiz-review.option :option="$option" :selected="$answer?->selected_option_id === $option->id" />
            @endforeach
        </div>
    @else
        <p class="text-body-lg text-on-surface p-space-lg border border-outline rounded-lg">
            {{ $answer?->answer_text ?: 'No answer' }}
        </p>
    @endif
</div>
