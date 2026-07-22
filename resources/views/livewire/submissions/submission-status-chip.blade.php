@php
    $status = $submission->status;
    [$icon, $color] = match ($status) {
        \App\Enums\SubmissionStatus::Pending => ['schedule', 'text-on-surface-variant bg-on-surface-variant/10'],
        \App\Enums\SubmissionStatus::Processing => ['autorenew', 'text-primary bg-primary/10'],
        \App\Enums\SubmissionStatus::Graded => ['check_circle', 'text-success bg-success/10'],
        \App\Enums\SubmissionStatus::Failed => ['error', 'text-error bg-error/10'],
    };
@endphp

<div @if (! $status->isTerminal()) wire:poll.3s="refreshStatus" @endif
    class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full {{ $color }}">
    <span class="material-symbols-outlined text-[16px]" data-weight="fill">{{ $icon }}</span>
    <span class="text-label-sm font-label-md">{{ $status->label() }}</span>

    @if ($status === \App\Enums\SubmissionStatus::Failed && $submission->retry_count)
        <span class="text-label-sm">(attempt {{ $submission->retry_count }})</span>
    @endif
</div>
