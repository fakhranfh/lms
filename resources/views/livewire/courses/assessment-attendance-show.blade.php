@section('title', $assessment->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div>
        <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Assessments
        </a>
        <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">{{ $assessment->title }}</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">Attendance &middot; Weight {{ rtrim(rtrim(number_format($assessment->weight, 2), '0'), '.') }}%</p>
    </div>

    @if ($isStudent)
        <div class="grid grid-cols-3 gap-space-md">
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Sessions Attended</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ $computed['attended'] }} / {{ $computed['total'] }}</p>
            </div>
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Percentage</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ $computed['percentage'] }}%</p>
            </div>
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Score</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ number_format($computed['score'], 1) }} / {{ rtrim(rtrim(number_format($assessment->weight, 2), '0'), '.') }}</p>
            </div>
        </div>
        <p class="text-body-xs text-on-surface-variant">Score is computed automatically from your attendance record &mdash; no submission is required.</p>
    @else
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container/50">
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Student</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Sessions Attended</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Percentage</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($studentRows as $row)
                            <tr wire:key="row-{{ $row['user']->id }}">
                                <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">{{ $row['user']->name }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['computed']['attended'] }} / {{ $row['computed']['total'] }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['computed']['percentage'] }}%</td>
                                <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">{{ number_format($row['computed']['score'], 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No students enrolled yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
