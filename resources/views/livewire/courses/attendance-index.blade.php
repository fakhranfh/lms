@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    @if ($successMessage)
        <div wire:click="clearSuccessMessage" class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md cursor-pointer">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    <div class="flex items-start justify-between">
        <h1 class="font-headline-md text-headline-md text-on-surface">Attendance</h1>
        @if ($canManage)
            <a href="{{ route('attendance.settings', $course) }}" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition inline-flex items-center gap-space-sm">
                <span class="material-symbols-outlined">settings</span>
                Manage attendance
            </a>
        @endif
    </div>

    @if ($isStudent)
        <!-- Attendance Summary -->
        <div class="grid grid-cols-3 gap-space-md">
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Total Sessions</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ $summary['total_session'] }}</p>
            </div>
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Total Attendance</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ $summary['total_attendance'] }}</p>
            </div>
            <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
                <p class="text-body-sm text-on-surface-variant">Minimal Attendance</p>
                <p class="font-headline-sm text-headline-sm text-on-surface mt-space-xs">{{ $summary['minimal_attendance'] }}</p>
            </div>
        </div>

        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container/50">
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Session</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Delivery</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Dates</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Attend</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Requirements</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($sessionRows as $row)
                            <tr wire:key="session-{{ $row['session']->id }}">
                                <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">{{ $row['session']->title }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ str($row['session']->delivery_mode->value)->replace('_', ' ')->title() }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                                    {{ $row['session']->date_start_display?->format('M j, Y, H:i') }} &ndash; {{ $row['session']->date_end_display?->format('H:i') }}
                                </td>
                                <td class="px-space-lg py-space-md">
                                    @if ($row['attend'])
                                        <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-success/10 text-success">
                                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                            Present
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-error/10 text-error">
                                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                                            Not Attended
                                        </span>
                                    @endif
                                </td>
                                <td class="px-space-lg py-space-md">
                                    @forelse ($row['checklist'] as $item)
                                        <span class="inline-flex items-center gap-space-xs text-body-xs {{ $item['is_fulfilled'] ? 'text-success' : 'text-on-surface-variant' }}">
                                            <span class="material-symbols-outlined text-[14px]">{{ $item['is_fulfilled'] ? 'check_box' : 'check_box_outline_blank' }}</span>
                                            {{ $item['requirement']->label }}
                                        </span>
                                        <br>
                                    @empty
                                        <span class="text-body-xs text-on-surface-variant">&mdash;</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No sessions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Teacher: session tabs -->
        <div class="flex flex-wrap gap-space-xs border-b border-outline-variant">
            @forelse ($sessions as $session)
                <button
                    type="button"
                    wire:click="selectSession('{{ $session->id }}')"
                    class="px-space-md py-space-sm rounded-t-lg border-b-2 font-label-sm text-label-sm transition {{ $selectedSession && $selectedSession->id === $session->id ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:bg-surface-container/50' }}"
                >
                    {{ $session->title }}
                </button>
            @empty
                <p class="text-body-sm text-on-surface-variant py-space-md">No sessions yet.</p>
            @endforelse
        </div>

        @if ($selectedSession)
            <div x-data="{}" class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-outline-variant bg-surface-container/50">
                                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Student</th>
                                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Requirements</th>
                                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Computed</th>
                                @if ($canManage)
                                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Override</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse ($studentRows as $row)
                                <tr
                                    wire:key="student-{{ $row['user']->id }}"
                                    x-data="{
                                        status: @js($row['attendance']?->status?->value ?? 'absent'),
                                        notes: @js($row['attendance']?->notes ?? ''),
                                    }"
                                >
                                    <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">{{ $row['user']->name }}</td>
                                    <td class="px-space-lg py-space-md">
                                        @forelse ($row['checklist'] as $item)
                                            <span class="inline-flex items-center gap-space-xs text-body-xs {{ $item['is_fulfilled'] ? 'text-success' : 'text-on-surface-variant' }}">
                                                <span class="material-symbols-outlined text-[14px]">{{ $item['is_fulfilled'] ? 'check_box' : 'check_box_outline_blank' }}</span>
                                                {{ $item['requirement']->label }}
                                            </span>
                                            <br>
                                        @empty
                                            <span class="text-body-xs text-on-surface-variant">&mdash;</span>
                                        @endforelse
                                    </td>
                                    <td class="px-space-lg py-space-md">
                                        @if ($row['attend'])
                                            <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-success/10 text-success">Present</span>
                                        @else
                                            <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-error/10 text-error">Not Attended</span>
                                        @endif
                                    </td>
                                    @if ($canManage)
                                        <td class="px-space-lg py-space-md">
                                            <div class="flex items-center gap-space-xs">
                                                <select x-model="status" class="px-space-sm py-1 border border-outline rounded-lg font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50">
                                                    @foreach ($statuses as $statusOption)
                                                        <option value="{{ $statusOption->value }}">{{ str($statusOption->value)->title() }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" x-model="notes" placeholder="Notes" class="px-space-sm py-1 border border-outline rounded-lg font-body-sm text-body-sm w-32 focus:outline-none focus:ring-2 focus:ring-primary/50" />
                                                <button
                                                    type="button"
                                                    @click="$wire.recordAttendance('{{ $selectedSession->id }}', '{{ $row['user']->id }}', status, notes)"
                                                    wire:loading.attr="disabled"
                                                    wire:target="recordAttendance"
                                                    class="px-space-sm py-1 bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-xs"
                                                >
                                                    <span wire:loading wire:target="recordAttendance" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                                                    Save
                                                </button>
                                            </div>
                                        </td>
                                    @endif
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
    @endif
</div>
