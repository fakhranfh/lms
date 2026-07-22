@php
    $hasOverride = $submission->instructor_score !== null;
@endphp

<div class="border border-outline rounded-lg p-space-lg space-y-space-md">
    <div class="flex items-center justify-between">
        <h2 class="font-title-md text-title-md text-on-surface">Result</h2>
        @if ($hasOverride)
            <span class="px-space-sm py-space-xs bg-primary/10 text-primary text-label-sm font-label-md rounded-full">Instructor Override</span>
        @endif
    </div>

    @if ($submission->status === \App\Enums\SubmissionStatus::Graded || $hasOverride)
        <div class="grid grid-cols-2 gap-space-md">
            @if ($hasOverride)
                <div>
                    <p class="text-label-sm text-on-surface-variant">AI Score</p>
                    <p class="text-headline-sm font-headline-sm text-on-surface">{{ $submission->ai_score ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-label-sm text-on-surface-variant">Instructor Score</p>
                    <p class="text-headline-sm font-headline-sm text-primary">{{ $submission->instructor_score }}</p>
                </div>
            @else
                <div class="col-span-2">
                    <p class="text-label-sm text-on-surface-variant">Score</p>
                    <p class="text-headline-sm font-headline-sm text-on-surface">{{ $submission->getDisplayScore() ?? '—' }}</p>
                </div>
            @endif
        </div>

        <div>
            <p class="text-label-sm text-on-surface-variant mb-space-xs">Feedback</p>
            <p class="text-body-md text-on-surface whitespace-pre-line">{{ $submission->getDisplayFeedback() ?? 'No feedback available.' }}</p>
        </div>

        @if (is_array($submission->ai_feedback) && !empty($submission->ai_feedback['rubric_scores'] ?? null))
            <div>
                <p class="text-label-sm text-on-surface-variant mb-space-xs">Rubric Feedback</p>
                <ul class="space-y-space-xs">
                    @foreach ($submission->ai_feedback['rubric_scores'] as $rubricScore)
                        <li class="text-body-sm text-on-surface-variant">
                            <span class="font-medium text-on-surface">{{ $rubricScore['criterion'] ?? '' }}</span>:
                            {{ $rubricScore['score'] ?? '' }}
                            @if (!empty($rubricScore['comment']))
                                &mdash; {{ $rubricScore['comment'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @elseif ($submission->status === \App\Enums\SubmissionStatus::Failed)
        <p class="text-body-md text-error">{{ $submission->error_message ?? 'Grading failed.' }}</p>
    @else
        <p class="text-body-md text-on-surface-variant">Grading in progress. This page will update automatically.</p>
    @endif
</div>
