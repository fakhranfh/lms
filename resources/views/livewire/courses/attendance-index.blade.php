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
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Attendance Requirement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse ($sessionRows as $index => $row)
                            <tr wire:key="session-{{ $row['session']->id }}">
                                <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">Session {{ $index + 1 }}</td>
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
                                <td class="px-space-lg py-space-md text-body-xs text-on-surface-variant">
                                    {{ $row['requirement'] }}
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
            @forelse ($sessions as $index => $session)
                <button
                    type="button"
                    wire:click="selectSession('{{ $session->id }}')"
                    class="px-space-md py-space-sm rounded-t-lg border-b-2 font-label-sm text-label-sm transition {{ $selectedSession && $selectedSession->id === $session->id ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:bg-surface-container/50' }}"
                >
                    Session {{ $index + 1 }}
                </button>
            @empty
                <p class="text-body-sm text-on-surface-variant py-space-md">No sessions yet.</p>
            @endforelse
        </div>

        @if ($selectedSession)
            <x-ui.search-input wire-model="studentSearch" placeholder="Search students..." class="max-w-sm" />

            <x-ui.pagination-links :paginator="$studentRows" />

            <!-- Skeleton Loading (shown while switching pages or sessions) -->
            <div
                wire:loading.delay.class.remove="hidden"
                wire:target="gotoPage,previousPage,nextPage,selectSession"
                class="hidden bg-surface border border-outline-variant rounded-lg overflow-hidden animate-pulse"
            >
                <x-attendance.table-skeleton :is-student="false" :can-manage="$canManage" />
            </div>

            <div wire:loading.remove wire:target="gotoPage,previousPage,nextPage,selectSession" x-data="{}" class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-outline-variant bg-surface-container/50">
                                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Student</th>
                                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Delivery</th>
                                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Attendance Requirement</th>
                                <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Status</th>
                                @if ($canManage)
                                    <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Mark Attendance</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @forelse ($studentRows as $row)
                                <tr wire:key="student-{{ $row['user']->id }}">
                                    <td class="px-space-lg py-space-md">
                                        <div class="flex items-center gap-space-sm">
                                            <x-avatar :user="$row['user']" size="8" />
                                            <span class="font-label-md text-label-md text-on-surface">{{ $row['user']->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ str($selectedSession->delivery_mode->value)->replace('_', ' ')->title() }}</td>
                                    <td class="px-space-lg py-space-md text-body-xs text-on-surface-variant">
                                        {{ $row['requirement'] }}
                                    </td>
                                    <td class="px-space-lg py-space-md">
                                        @if ($row['selfAttendedAt'])
                                            <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-success/10 text-success">Present (self check-in)</span>
                                            <p class="text-body-xs text-on-surface-variant mt-1">{{ $row['selfAttendedAt']->format('d M Y, H:i') }}</p>
                                        @elseif ($row['attend'])
                                            <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-success/10 text-success">Present</span>
                                        @else
                                            <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-error/10 text-error">Not Attended</span>
                                        @endif
                                    </td>
                                    @if ($canManage)
                                        <td class="px-space-lg py-space-md">
                                            <div
                                                class="flex items-center gap-space-md"
                                                x-data="{ status: @entangle('drafts.'.$row['user']->id.'.status').live, notesModalOpen: false }"
                                            >
                                                <div class="flex flex-col items-start gap-1">
                                                    @foreach ($statuses as $statusOption)
                                                        <label class="inline-flex items-center gap-1 text-body-xs text-on-surface cursor-pointer">
                                                            <input
                                                                type="radio"
                                                                name="status-{{ $row['user']->id }}"
                                                                value="{{ $statusOption->value }}"
                                                                x-model="status"
                                                                class="w-3.5 h-3.5 accent-primary"
                                                            />
                                                            {{ str($statusOption->value)->title() }}
                                                        </label>
                                                    @endforeach

                                                    <button
                                                        type="button"
                                                        x-show="status === 'excused'"
                                                        x-cloak
                                                        @click="notesModalOpen = true"
                                                        class="mt-1 inline-flex items-center gap-1 text-body-xs text-primary hover:underline"
                                                    >
                                                        <span class="material-symbols-outlined text-[14px]">note_add</span>
                                                        Add Notes
                                                    </button>
                                                </div>

                                                <div x-show="notesModalOpen" x-cloak class="fixed inset-0 z-50">
                                                    <div @click="notesModalOpen = false" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

                                                    <div class="fixed inset-0 flex items-center justify-center p-4">
                                                        <div @click.stop class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-lg w-full">
                                                            <div class="p-space-lg space-y-space-md">
                                                                <h3 class="font-headline-sm text-headline-sm text-on-surface">Excuse Notes &mdash; {{ $row['user']->name }}</h3>

                                                                <x-rich-text-editor
                                                                    id="attendance-notes-{{ $row['user']->id }}"
                                                                    wire-model="drafts.{{ $row['user']->id }}.notes"
                                                                    :value="$this->drafts[$row['user']->id]['notes'] ?? ''"
                                                                    :allow-attachments="false"
                                                                />

                                                                <div class="flex justify-end pt-space-sm">
                                                                    <button
                                                                        type="button"
                                                                        @click="notesModalOpen = false"
                                                                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                                                                    >
                                                                        Done
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-ui.pagination-links :paginator="$studentRows" />

            @if ($canManage)
                <div class="h-16" aria-hidden="true"></div>

                <div class="relative sticky bottom-[-1.5rem] z-20 -mx-gutter px-gutter pt-space-md pb-space-lg bg-surface border-t border-outline-variant flex items-center justify-between gap-space-md before:content-[''] before:absolute before:left-0 before:right-0 before:-top-space-lg before:h-space-lg before:bg-surface before:-z-10">
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Mark attendance changes are saved as drafts &mdash; click Save All to apply them.</p>
                    <button
                        type="button"
                        wire:click="saveAllAttendance"
                        wire:loading.attr="disabled"
                        wire:target="saveAllAttendance"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm flex-shrink-0"
                    >
                        <span wire:loading wire:target="saveAllAttendance" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        Save All
                    </button>
                </div>
            @endif
        @endif
    @endif
</div>
