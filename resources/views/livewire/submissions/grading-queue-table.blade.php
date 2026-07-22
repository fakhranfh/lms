@section('title', 'Grading Queue')

<div class="space-y-space-lg">
    <div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Grading Queue</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">Review and grade student submissions</p>
    </div>

    <div class="grid grid-cols-4 gap-space-md">
        <select wire:model.live="status" class="px-space-md py-space-sm border border-outline rounded-lg">
            <option value="">All statuses</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="assignmentId" class="px-space-md py-space-sm border border-outline rounded-lg">
            <option value="">All assignments</option>
            @foreach ($assignments as $assignment)
                <option value="{{ $assignment->id }}">{{ $assignment->title }}</option>
            @endforeach
        </select>

        <input type="date" wire:model.live="submittedFrom" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <input type="date" wire:model.live="submittedTo" class="px-space-md py-space-sm border border-outline rounded-lg" />
    </div>

    <div class="overflow-x-auto border border-outline rounded-lg">
        <table class="w-full text-left">
            <thead class="bg-surface-container text-label-sm text-on-surface-variant">
                <tr>
                    <th class="px-space-md py-space-sm">Student</th>
                    <th class="px-space-md py-space-sm">Assignment</th>
                    <th class="px-space-md py-space-sm">Status</th>
                    <th class="px-space-md py-space-sm">AI Score</th>
                    <th class="px-space-md py-space-sm">Submitted</th>
                    <th class="px-space-md py-space-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $submission)
                    <tr class="border-t border-outline/30">
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->user->name }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->assignment->title }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->status->label() }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->ai_score ?? '—' }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->submitted_at?->format('M j, Y g:i A') }}</td>
                        <td class="px-space-md py-space-sm text-body-sm space-x-space-sm">
                            <a href="{{ route('submissions.show', $submission) }}" class="text-primary hover:underline">View</a>
                            @can('submissions.override-grade')
                                <a href="{{ route('submissions.override', $submission) }}" class="text-primary hover:underline">Override</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">No submissions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $submissions->links() }}
</div>
