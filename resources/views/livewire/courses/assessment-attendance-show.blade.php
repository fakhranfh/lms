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

    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-outline-variant bg-surface-container/50">
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Title</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Start Date</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Due Date</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Status</th>
                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @if ($isStudent)
                        @forelse ($sessionRows as $row)
                            <tr wire:key="session-{{ $row['session']->id }}">
                                <td class="px-space-lg py-space-md">
                                    <p class="font-label-md text-label-md text-on-surface">{{ $row['session']->title }}</p>
                                </td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['session']->date_start_display?->format('d M Y, H:i') }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['session']->date_end_display?->format('d M Y, H:i') }}</td>
                                <td class="px-space-lg py-space-md">
                                    @if ($row['attended'])
                                        <span class="inline-flex items-center gap-1 text-body-sm text-on-surface">
                                            Completed
                                            <span class="material-symbols-outlined text-[18px] text-white bg-green-600 rounded-sm">check</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-body-sm text-on-surface-variant">
                                            Not attended
                                            <span class="material-symbols-outlined text-[18px] text-white bg-outline-variant rounded-sm">close</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">{{ $row['attended'] ? '100 pts' : '0 pts' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No virtual class sessions yet.</td>
                            </tr>
                        @endforelse
                    @else
                        @forelse ($sessionRows as $row)
                            <tr wire:key="session-{{ $row['session']->id }}">
                                <td class="px-space-lg py-space-md">
                                    <p class="font-label-md text-label-md text-on-surface">{{ $row['session']->title }}</p>
                                </td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['session']->date_start_display?->format('d M Y, H:i') }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['session']->date_end_display?->format('d M Y, H:i') }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface" colspan="2">{{ $row['attendedCount'] }} of {{ $row['totalStudents'] }} students attended</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No virtual class sessions yet.</td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if ($isStudent)
        <p class="text-body-xs text-on-surface-variant">Score is computed automatically from your attendance record &mdash; no submission is required.</p>
    @endif
</div>
