{{-- Molecule: the question navigator sidebar in a quiz review modal. --}}
@props(['questions', 'answers'])

<div class="overflow-y-auto p-space-lg border-b md:border-b-0 md:border-r border-outline-variant">
    <p class="font-label-sm text-label-sm text-secondary mb-space-md">Questions</p>
    <div class="grid grid-cols-6 md:grid-cols-4 gap-space-xs">
        @foreach ($questions as $question)
            <x-assessments.quiz-review.nav-button :index="$loop->index" :correct="$answers->get($question->id)?->selectedOption?->is_correct" />
        @endforeach
    </div>
</div>
