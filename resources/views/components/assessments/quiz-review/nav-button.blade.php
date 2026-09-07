{{--
    Atom: one question-navigator button in a quiz review modal. Relies on
    a `currentQuestion` Alpine property owned by the enclosing modal.
--}}
@props(['index', 'correct'])

<button
    type="button"
    @click="currentQuestion = {{ $index }}"
    :class="currentQuestion === {{ $index }} ? 'ring-2 ring-primary ring-offset-2' : ''"
    class="w-10 h-10 rounded-lg font-label-sm text-label-sm flex items-center justify-center transition {{ $correct === true ? 'bg-success text-white' : ($correct === false ? 'bg-error text-white' : 'bg-surface-container text-on-surface hover:bg-surface-container/70') }}"
>
    {{ $index + 1 }}
</button>
