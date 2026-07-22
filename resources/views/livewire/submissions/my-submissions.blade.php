@section('title', 'My Submissions')

<div class="space-y-space-lg">
    <div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">My Submissions</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">Track the status and results of your assignment submissions</p>
    </div>

    <div class="overflow-x-auto border border-outline rounded-lg">
        <table class="w-full text-left">
            <thead class="bg-surface-container text-label-sm text-on-surface-variant">
                <tr>
                    <th class="px-space-md py-space-sm">Assignment</th>
                    <th class="px-space-md py-space-sm">Status</th>
                    <th class="px-space-md py-space-sm">Score</th>
                    <th class="px-space-md py-space-sm">Submitted</th>
                    <th class="px-space-md py-space-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $submission)
                    <tr class="border-t border-outline/30">
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->assignment->title }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->status->label() }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->getDisplayScore() ?? '—' }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $submission->submitted_at?->format('M j, Y g:i A') }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">
                            <a href="{{ route('submissions.show', $submission) }}" class="text-primary hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">You haven't submitted any assignments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $submissions->links() }}
</div>
