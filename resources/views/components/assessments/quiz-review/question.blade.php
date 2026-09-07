{{--
    Molecule: one question's description, points, and answer review in a
    quiz review modal. Relies on a `currentQuestion` Alpine property owned
    by the enclosing modal to toggle visibility.
--}}
@props(['question', 'index', 'total', 'answer'])

<div x-show="currentQuestion === {{ $index }}" x-cloak class="space-y-space-lg max-w-2xl mx-auto">
    <div class="flex items-center justify-between">
        <p class="text-body-sm text-on-surface-variant">Question {{ $index + 1 }} of {{ $total }}</p>
        <p class="text-body-sm font-medium text-on-surface">
            {{ $answer?->score !== null ? rtrim(rtrim(number_format($answer->score, 2), '0'), '.') : '—' }} / {{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts
        </p>
    </div>
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
