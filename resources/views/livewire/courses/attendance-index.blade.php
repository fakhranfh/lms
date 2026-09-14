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

    @if (app()->isLocal() && ! $isStudent && $canManage)
        <div x-data="{ confirmResetOpen: false }" class="px-gutter py-space-md bg-secondary/10 border border-secondary/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-secondary text-[20px]">science</span>
            <p class="font-body-sm text-body-sm text-secondary flex-1">Dev only: reset all student attendance for this course.</p>
            <button
                type="button"
                @click="confirmResetOpen = true"
                wire:loading.attr="disabled"
                wire:target="resetAllAttendance"
                class="px-space-md py-space-xs rounded-lg border border-error text-error font-label-sm text-label-sm hover:bg-error/10 transition disabled:opacity-50 inline-flex items-center gap-space-xs"
            >
                <span wire:loading wire:target="resetAllAttendance" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                Reset All Attendance
            </button>

            <x-ui.modal show="confirmResetOpen" onClose="confirmResetOpen = false" maxWidth="max-w-sm">
                <div class="bg-surface border border-outline-variant rounded-lg shadow-lg">
                    <div class="p-space-lg space-y-space-lg">
                        <div class="flex justify-center">
                            <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                                <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">warning</span>
                            </div>
                        </div>

                        <div class="text-center space-y-space-sm">
                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Reset All Attendance?</h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                This deletes every student's attendance record for this course and unlocks all sessions. This cannot be undone.
                            </p>
                        </div>

                        <div class="flex gap-space-md pt-space-md">
                            <button
                                type="button"
                                @click="confirmResetOpen = false"
                                class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="confirmResetOpen = false"
                                wire:click="resetAllAttendance"
                                wire:loading.attr="disabled"
                                wire:target="resetAllAttendance"
                                class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center justify-center gap-space-sm"
                            >
                                <span wire:loading wire:target="resetAllAttendance" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </x-ui.modal>
        </div>
    @endif

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
                        @forelse ($sessionRows as $row)
                            <tr wire:key="session-{{ $row['session']->id }}">
                                <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">Session {{ $row['session']->order }}</td>
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
            @forelse ($sessions as $session)
                <button
                    type="button"
                    wire:click="selectSession('{{ $session->id }}')"
                    onclick="this.closest('div').querySelectorAll('button').forEach(el => el.classList.remove('border-primary', 'text-primary')); this.closest('div').querySelectorAll('button').forEach(el => el.classList.add('border-transparent', 'text-on-surface-variant')); this.classList.remove('border-transparent', 'text-on-surface-variant'); this.classList.add('border-primary', 'text-primary');"
                    class="px-space-md py-space-sm rounded-t-lg border-b-2 font-label-sm text-label-sm transition {{ $selectedSession && $selectedSession->id === $session->id ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:bg-surface-container/50' }}"
                >
                    Session {{ $session->order }}
                </button>
            @empty
                <p class="text-body-sm text-on-surface-variant py-space-md">No sessions yet.</p>
            @endforelse
        </div>

        @if ($selectedSession)
            <x-ui.search-input wire-model="studentSearch" placeholder="Search students..." class="max-w-sm" />

            <x-ui.pagination-links :paginator="$studentRows" />

            <!-- Skeleton Loading (shown while switching pages, sessions, searching, or saving) -->
            <div
                wire:loading.class.remove="hidden"
                wire:target="gotoPage,previousPage,nextPage,selectSession,studentSearch,saveAllAttendance"
                class="hidden bg-surface border border-outline-variant rounded-lg overflow-hidden animate-pulse"
            >
                <x-attendance.table-skeleton :is-student="false" :can-manage="$canManage" />
            </div>

            <div wire:loading.remove wire:target="gotoPage,previousPage,nextPage,selectSession,studentSearch,saveAllAttendance" x-data="{}" class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
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
                                        @elseif ($row['teacherRecordedAt'])
                                            <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-success/10 text-success">Present (marked by teacher)</span>
                                            <p class="text-body-xs text-on-surface-variant mt-1">{{ $row['teacherRecordedAt']->format('d M Y, H:i') }}</p>
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
                                                x-data="{
                                                    status: @entangle('drafts.'.$row['user']->id.'.status').live,
                                                    notes: @entangle('drafts.'.$row['user']->id.'.notes'),
                                                    notesModalOpen: false,
                                                    get hasNotes() { return !!(this.notes && this.notes.replace(/<[^>]*>/g, '').trim() !== ''); },
                                                }"
                                            >
                                                <div class="flex flex-col items-start gap-1">
                                                    @foreach ($statuses as $statusOption)
                                                        <label class="inline-flex items-center gap-1 text-body-xs text-on-surface {{ $isLocked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                                                            <input
                                                                type="radio"
                                                                name="status-{{ $row['user']->id }}"
                                                                value="{{ $statusOption->value }}"
                                                                x-model="status"
                                                                @disabled($isLocked)
                                                                class="w-3.5 h-3.5 accent-primary disabled:cursor-not-allowed"
                                                            />
                                                            {{ str($statusOption->value)->title() }}
                                                        </label>
                                                    @endforeach

                                                    <button
                                                        type="button"
                                                        x-show="status === 'excused'"
                                                        x-cloak
                                                        @click="notesModalOpen = true"
                                                        @disabled($isLocked)
                                                        class="mt-1 inline-flex items-center gap-1 text-body-xs hover:underline disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:no-underline"
                                                        :class="hasNotes ? 'text-success' : 'text-primary'"
                                                    >
                                                        <span class="material-symbols-outlined text-[14px]" x-text="hasNotes ? 'check_circle' : 'note_add'"></span>
                                                        <span x-text="hasNotes ? 'Notes added' : 'Add Notes'"></span>
                                                    </button>
                                                </div>

                                                <x-ui.modal show="notesModalOpen" onClose="notesModalOpen = false" maxWidth="max-w-lg">
                                                    <div class="bg-surface border border-outline-variant rounded-lg shadow-lg">
                                                        <div class="p-space-lg space-y-space-md">
                                                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Excuse Notes &mdash; {{ $row['user']->name }}</h3>

                                                            <x-rich-text-editor
                                                                id="attendance-notes-{{ $row['user']->id }}"
                                                                wire-model="drafts.{{ $row['user']->id }}.notes"
                                                                :value="$this->drafts[$row['user']->id]['notes'] ?? ''"
                                                                :allow-attachments="false"
                                                                :disabled="$isLocked"
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
                                                </x-ui.modal>
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
                    @if ($isLocked)
                        <p class="font-body-sm text-body-sm text-on-surface-variant inline-flex items-center gap-space-xs">
                            <span class="material-symbols-outlined text-[18px] text-success">lock</span>
                            Attendance for this session has been saved and locked &mdash; it can no longer be changed.
                        </p>
                    @else
                        <div x-data="{ confirmOpen: false }" class="contents">
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Mark attendance changes are saved as drafts &mdash; click Save All to apply them.</p>
                            <button
                                type="button"
                                @click="confirmOpen = true"
                                wire:loading.attr="disabled"
                                wire:target="saveAllAttendance"
                                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center gap-space-sm flex-shrink-0"
                            >
                                <span wire:loading wire:target="saveAllAttendance" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                Save All
                            </button>

                            <x-ui.modal show="confirmOpen" onClose="confirmOpen = false" maxWidth="max-w-sm">
                                <div class="bg-surface border border-outline-variant rounded-lg shadow-lg">
                                    <div class="p-space-lg space-y-space-lg">
                                        <div class="flex justify-center">
                                            <div class="flex items-center justify-center w-12 h-12 bg-warning/10 rounded-full">
                                                <span class="material-symbols-outlined text-warning text-[24px]" data-weight="fill">warning</span>
                                            </div>
                                        </div>

                                        <div class="text-center space-y-space-sm">
                                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Save and Lock Attendance?</h3>
                                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                                Once saved, attendance for this session can no longer be changed. Make sure every student's status is correct before continuing.
                                            </p>
                                        </div>

                                        <div class="flex gap-space-md pt-space-md">
                                            <button
                                                type="button"
                                                @click="confirmOpen = false"
                                                class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmOpen = false"
                                                wire:click="saveAllAttendance"
                                                wire:loading.attr="disabled"
                                                wire:target="saveAllAttendance"
                                                class="flex-1 px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-50 inline-flex items-center justify-center gap-space-sm"
                                            >
                                                <span wire:loading wire:target="saveAllAttendance" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                                                Save & Lock
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </x-ui.modal>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    @endif
</div>
