@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="font-headline-md text-headline-md text-on-surface">Assessment</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">{{ collect($groupedAssessments)->sum(fn ($g) => $g['assessments']->count()) }} assessment(s)</p>
        </div>

        @unless ($isStudent)
            <div class="flex items-center gap-space-sm">
                <a
                    href="{{ route('groups.manage', $course) }}"
                    class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition inline-flex items-center gap-space-sm"
                >
                    <span class="material-symbols-outlined">groups</span>
                    Manage Groups
                </a>

                <div class="relative" x-data="{ open: false }">
                    <button
                        type="button"
                        @click="open = !open"
                        @click.outside="open = false"
                        class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
                    >
                        <span class="material-symbols-outlined">add</span>
                        Create Assessment
                    </button>

                    <div x-show="open" x-cloak class="absolute right-0 mt-space-xs w-56 bg-surface border border-outline-variant rounded-lg shadow-lg z-10 overflow-hidden">
                        <a href="{{ route('assessments.create', [$course, 'personal']) }}" class="block px-space-md py-space-sm text-body-sm text-on-surface hover:bg-surface-container">
                            Personal Assignment
                        </a>
                        <a href="{{ route('assessments.create', [$course, 'team']) }}" class="block px-space-md py-space-sm text-body-sm text-on-surface hover:bg-surface-container">
                            Team Assignment
                        </a>
                        <a href="{{ route('assessments.quiz.create', $course) }}" class="block px-space-md py-space-sm text-body-sm text-on-surface hover:bg-surface-container">
                            Quiz
                        </a>
                        <span class="block px-space-md py-space-sm text-body-sm text-on-surface-variant/60 cursor-not-allowed">Final Exam &mdash; coming soon</span>
                        <span class="block px-space-md py-space-sm text-body-sm text-on-surface-variant/60 cursor-not-allowed">Forum Discussion &mdash; coming soon</span>
                    </div>
                </div>
            </div>
        @endunless
    </div>

    <!-- Grouped Collapsible Tables -->
    <div class="space-y-space-lg" x-data="{ deleteId: null, deleteName: null, showDeleteModal: false, deleteConfirmText: '' }">
        @foreach ($groupedAssessments as $index => $group)
            <div x-data="{ open: @js($group['isExpanded']) }">
                @if ($group['assessments']->isNotEmpty())
                    <!-- Collapsible Header -->
                    <button
                        type="button"
                        wire:click="toggleSection('{{ $group['sectionKey'] }}')"
                        @click="open = !open"
                        class="w-full flex items-center justify-between px-space-lg py-space-md bg-surface border border-outline-variant rounded-lg hover:bg-surface-container/50 transition"
                    >
                        <div class="flex items-center gap-space-md flex-1">
                            <span class="material-symbols-outlined text-on-surface-variant transition-transform" :class="open ? 'rotate-90' : ''">
                                chevron_right
                            </span>
                            <h2 class="font-label-lg text-label-lg text-on-surface">
                                {{ strtoupper(\App\Support\AssessmentTypeLabel::forType($group['type'])) }}: {{ rtrim(rtrim(number_format($group['totalWeight'], 2), '0'), '.') }}%
                            </h2>
                        </div>
                        <span class="text-body-sm text-on-surface-variant flex-shrink-0">
                            {{ $group['assessments']->count() }} assessment{{ $group['assessments']->count() !== 1 ? 's' : '' }}
                        </span>
                    </button>

                    <!-- Collapsible Content: Table -->
                    <div x-show="open" x-cloak class="bg-surface border border-t-0 border-outline-variant rounded-b-lg overflow-hidden">
                        @if ($group['type'] === \App\Enums\AssessmentType::Attendance && $isStudent)
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
                                        @forelse ($group['sessionRows'] as $sessionRow)
                                            <tr
                                                wire:key="attendance-session-{{ $sessionRow['session']->id }}"
                                                @click="window.location = '{{ route('sessions.index', $course) }}?session={{ $sessionRow['session']->id }}'"
                                                class="hover:bg-surface-container/30 transition cursor-pointer"
                                            >
                                                <td class="px-space-lg py-space-md">
                                                    <p class="font-label-md text-label-md text-on-surface">{{ str($sessionRow['session']->title)->before(' - ') }}</p>
                                                    <p class="text-body-xs text-on-surface-variant">{{ $sessionRow['session']->title }}</p>
                                                </td>
                                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                                                    {{ $sessionRow['session']->date_start_display?->format('d M Y,') }}<br>
                                                    {{ $sessionRow['session']->date_start_display?->format('H:i') }} {{ $sessionRow['session']->date_start_display?->format('T') }}
                                                </td>
                                                <td class="px-space-lg py-space-md">
                                                    <div class="text-body-sm text-on-surface">
                                                        {{ $sessionRow['session']->date_end_display?->format('d M Y,') }}<br>
                                                        {{ $sessionRow['session']->date_end_display?->format('H:i') }} {{ $sessionRow['session']->date_end_display?->format('T') }}
                                                    </div>
                                                    @if ($sessionRow['session']->date_end_display?->isPast())
                                                        <span class="inline-flex items-center px-space-xs py-1 mt-1 rounded-full text-body-xs font-medium bg-on-surface-variant/20 text-on-surface">
                                                            Expired
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-space-lg py-space-md">
                                                    @if ($sessionRow['attended'])
                                                        <span class="inline-flex items-center gap-1 text-body-sm text-on-surface">
                                                            Completed
                                                            <span class="material-symbols-outlined text-[16px] text-on-primary bg-success rounded-sm">check</span>
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 text-body-sm text-on-surface-variant">
                                                            Not attended
                                                            <span class="material-symbols-outlined text-[16px] text-on-primary bg-outline-variant rounded-sm">close</span>
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-space-lg py-space-md font-label-md text-label-md text-on-surface">{{ $sessionRow['attended'] ? '100 pts' : '0 pts' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No virtual class sessions yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-outline-variant bg-surface-container/50">
                                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Title</th>
                                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Assigned to</th>
                                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Start Date</th>
                                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Due Date</th>
                                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Status</th>
                                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Attempt</th>
                                        <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Score</th>
                                        @unless ($isStudent)
                                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Actions</th>
                                        @endunless
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant">
                                    @foreach ($group['assessments'] as $item)
                                        <tr
                                            wire:key="assessment-{{ $item['data']->id }}"
                                            @if ($item['row']['route'])
                                                @click="window.location = '{{ $item['row']['route'] }}'"
                                                class="hover:bg-surface-container/30 transition cursor-pointer"
                                            @else
                                                class="hover:bg-surface-container/30 transition"
                                            @endif
                                        >
                                            <td class="px-space-lg py-space-md">
                                                @if ($item['row']['route'])
                                                    <span class="text-on-surface font-label-md text-label-md">
                                                        {{ $item['data']->title }}
                                                    </span>
                                                @else
                                                    <span class="text-on-surface-variant opacity-60 font-label-md text-label-md">
                                                        {{ $item['data']->title }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                                                <span class="inline-flex items-center gap-space-xs">
                                                    <span class="material-symbols-outlined text-[16px]">
                                                        {{ $item['data']->assigned_to->value === 'individual' ? 'person' : 'groups' }}
                                                    </span>
                                                    {{ str($item['data']->assigned_to->value)->title() }}
                                                </span>
                                            </td>
                                            <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                                                @if ($item['data']->start_date)
                                                    {{ $item['data']->start_date->format('M j, Y, H:i') }}
                                                @else
                                                    <span class="text-on-surface-variant">—</span>
                                                @endif
                                            </td>
                                            <td class="px-space-lg py-space-md">
                                                <div class="flex items-center gap-space-xs">
                                                    @if ($item['data']->end_date)
                                                        <span class="text-body-sm text-on-surface">{{ $item['data']->end_date->format('M j, Y, H:i') }}</span>
                                                        @if ($item['row']['isExpired'])
                                                            <span class="inline-flex items-center px-space-xs py-1 rounded-full text-body-xs font-medium bg-error/10 text-error">
                                                                Expired
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-on-surface-variant">—</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-space-lg py-space-md">
                                                <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm {{ $item['row']['statusConfig']['bg'] }} {{ $item['row']['statusConfig']['text'] }}">
                                                    <span class="material-symbols-outlined text-[16px]">{{ $item['row']['statusConfig']['icon'] }}</span>
                                                    {{ str($item['row']['status'])->replace('_', ' ')->title() }}
                                                </span>
                                            </td>
                                            <td class="px-space-lg py-space-md text-body-sm text-on-surface">
                                                @if ($isStudent && $item['row']['route'])
                                                    {{ $item['row']['attemptCount'] }} of {{ $item['row']['attemptLimit'] }}
                                                @else
                                                    <span class="text-on-surface-variant">—</span>
                                                @endif
                                            </td>
                                            <td class="px-space-lg py-space-md text-body-sm text-on-surface font-label-md">
                                                @if ($item['row']['score'] !== null)
                                                    {{ number_format($item['row']['score'], 1) }}
                                                @else
                                                    <span class="text-on-surface-variant">—</span>
                                                @endif
                                            </td>
                                            @unless ($isStudent)
                                                <td class="px-space-lg py-space-md" @click.stop>
                                                    <div class="flex gap-space-sm">
                                                        @if ($item['row']['route'])
                                                            <a
                                                                href="{{ $item['data']->type->value === 'theory_quiz' ? route('assessments.quiz.edit', $item['data']) : route('assessments.edit', $item['data']) }}"
                                                                class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex"
                                                                title="Edit assessment"
                                                            >
                                                                <span class="material-symbols-outlined">edit</span>
                                                            </a>

                                                            @if ($item['data']->type !== \App\Enums\AssessmentType::Attendance)
                                                                <button
                                                                    type="button"
                                                                    @click.stop="deleteId = @js($item['data']->id); deleteName = @js($item['data']->title); deleteConfirmText = ''; showDeleteModal = true"
                                                                    class="p-2 hover:bg-surface-container rounded transition text-error"
                                                                >
                                                                    <span class="material-symbols-outlined">delete</span>
                                                                </button>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                            @endunless
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                @else
                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center text-body-sm text-on-surface-variant">
                        No {{ strtolower(\App\Support\AssessmentTypeLabel::forType($group['type'])) }} yet.
                    </div>
                @endif
            </div>
        @endforeach

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50">
            <div
                @click="showDeleteModal = false"
                class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            ></div>

            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                    <div class="p-space-lg space-y-space-lg">
                        <div class="flex justify-center">
                            <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                                <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                            </div>
                        </div>

                        <div class="text-center space-y-space-sm">
                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Delete Confirmation</h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Are you sure you want to delete "<span class="font-medium" x-text="deleteName ?? 'this assessment'"></span>"?
                                This action cannot be undone.
                            </p>
                        </div>

                        <div class="text-left">
                            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">
                                Type <span class="font-medium" x-text="deleteName"></span> to confirm
                            </label>
                            <input
                                type="text"
                                x-model="deleteConfirmText"
                                autocomplete="off"
                                class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                            />
                        </div>

                        <div class="flex gap-space-md pt-space-md">
                            <button
                                @click="showDeleteModal = false"
                                type="button"
                                class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                            >
                                Cancel
                            </button>
                            <button
                                :disabled="deleteConfirmText !== deleteName"
                                :class="deleteConfirmText !== deleteName ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'"
                                @click="showDeleteModal = false; $wire.call('deleteAssessment', deleteId)"
                                type="button"
                                class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md transition-opacity"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
