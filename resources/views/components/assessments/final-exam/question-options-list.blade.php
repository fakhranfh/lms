{{--
    Molecule: the full option list for a multiple-choice question, reused
    both on the read-only question list (no `selectedOptionId`) and on the
    grade page (marking whichever option the student picked).
--}}
@props(['question', 'selectedOptionId' => null])

<div class="space-y-space-xs">
    @foreach ($question->options as $option)
        <x-assessments.final-exam.question-option :option="$option" :selected="$selectedOptionId === $option->id" />
    @endforeach
</div>
