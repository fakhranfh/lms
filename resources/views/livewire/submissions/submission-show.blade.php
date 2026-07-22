@section('title', 'Submission')

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="w-full max-w-3xl mx-auto space-y-space-lg">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-headline-sm text-headline-sm text-on-surface">{{ $submission->assignment->title }}</h1>
                <p class="text-body-sm text-on-surface-variant mt-1">Submitted by {{ $submission->user->name }} on {{ $submission->submitted_at?->format('M j, Y g:i A') }}</p>
            </div>
            <livewire:submissions.submission-status-chip :submission="$submission" :key="'chip-'.$submission->id" />
        </div>

        <div class="border border-outline rounded-lg p-space-lg">
            <p class="text-label-sm text-on-surface-variant mb-space-xs">Your Answer</p>
            <p class="text-body-md text-on-surface whitespace-pre-line">{{ $submission->student_answer }}</p>
        </div>

        <livewire:submissions.submission-result-panel :submission="$submission" :key="'panel-'.$submission->id" />

        @can('submissions.override-grade')
            <a href="{{ route('submissions.override', $submission) }}" class="inline-flex px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md">
                Override Score
            </a>
        @endcan

        @if ($pastAttempts->isNotEmpty())
            <div class="border border-outline rounded-lg p-space-lg">
                <h2 class="font-title-md text-title-md text-on-surface mb-space-sm">Previous Attempts</h2>
                <ul class="space-y-space-xs">
                    @foreach ($pastAttempts as $attempt)
                        <li>
                            <a href="{{ route('submissions.show', $attempt) }}" class="text-primary text-body-sm hover:underline">
                                {{ $attempt->submitted_at?->format('M j, Y g:i A') }} &mdash; {{ $attempt->status->label() }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
