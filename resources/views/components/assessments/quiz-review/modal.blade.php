{{--
    Organism: full-screen quiz review modal — question navigator, per-question
    answer review, and final score/feedback. Used both for a student
    reviewing their own past attempt and for a teacher reviewing a student's
    submission (assessment-quiz-show.blade.php).

    `show`/`onClose` are raw Alpine expressions evaluated in the caller's
    x-data scope (matches x-ui.modal's convention), so callers own the
    open/close state (typically a per-card `x-data="{ open: false }"`).
--}}
@props(['show', 'onClose', 'title', 'questions', 'answers', 'total', 'feedback' => null])

<template x-teleport="body">
    <div
        x-show="{{ $show }}"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] bg-surface flex flex-col"
    >
        <div class="flex items-start justify-between px-space-lg py-space-md border-b border-outline-variant flex-shrink-0">
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-space-sm">{{ $title }}</h2>
                <x-assessments.quiz-review.score-badge :total="$total" />
            </div>
            <button type="button" @click="{{ $onClose }}" class="p-2 hover:bg-surface-container rounded transition">
                <span class="material-symbols-outlined text-on-surface-variant">close</span>
            </button>
        </div>

        <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-[220px_1fr]" x-data="{ currentQuestion: 0 }">
            <x-assessments.quiz-review.nav :questions="$questions" :answers="$answers" />

            <div class="overflow-y-auto p-space-xl">
                @foreach ($questions as $question)
                    <x-assessments.quiz-review.question
                        :question="$question"
                        :index="$loop->index"
                        :total="$questions->count()"
                        :answer="$answers->get($question->id)"
                    />
                @endforeach

                <div class="flex items-center justify-between pt-space-lg mt-space-lg border-t border-outline-variant max-w-2xl mx-auto">
                    <button
                        type="button"
                        @click="currentQuestion = Math.max(currentQuestion - 1, 0)"
                        :disabled="currentQuestion === 0"
                        class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition disabled:opacity-50"
                    >
                        Previous
                    </button>

                    <button
                        type="button"
                        x-show="currentQuestion < {{ $questions->count() - 1 }}"
                        @click="currentQuestion = Math.min(currentQuestion + 1, {{ $questions->count() - 1 }})"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    >
                        Next
                    </button>
                </div>

                @if ($feedback)
                    <div class="pt-space-lg mt-space-lg border-t border-outline-variant max-w-2xl mx-auto">
                        <p class="text-body-xs text-on-surface-variant mb-space-xs">Feedback</p>
                        <p class="text-body-sm text-on-surface">{{ $feedback }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</template>
