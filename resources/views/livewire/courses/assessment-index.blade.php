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
                        <span class="block px-space-md py-space-sm text-body-sm text-on-surface-variant/60 cursor-not-allowed">Quiz &mdash; coming soon</span>
                        <span class="block px-space-md py-space-sm text-body-sm text-on-surface-variant/60 cursor-not-allowed">Final Exam &mdash; coming soon</span>
                        <span class="block px-space-md py-space-sm text-body-sm text-on-surface-variant/60 cursor-not-allowed">Forum Discussion &mdash; coming soon</span>
                        <span class="block px-space-md py-space-sm text-body-sm text-on-surface-variant/60 cursor-not-allowed">Attendance &mdash; coming soon</span>
                    </div>
                </div>
            </div>
        @endunless
    </div>

    <!-- Grouped list -->
    <div class="space-y-space-lg" x-data="{ deleteId: null, deleteName: null, showDeleteModal: false, deleteConfirmText: '' }">
        @foreach ($groupedAssessments as $group)
            <div>
                <h2 class="font-label-lg text-label-lg text-on-surface-variant mb-space-sm">
                    {{ \App\Support\AssessmentTypeLabel::forType($group['type']) }}
                </h2>

                @if ($group['assessments']->isEmpty())
                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg text-center text-body-sm text-on-surface-variant">
                        No {{ strtolower(\App\Support\AssessmentTypeLabel::forType($group['type'])) }} yet.
                    </div>
                @else
                    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden divide-y divide-outline-variant">
                        @foreach ($group['assessments'] as $assessment)
                            @php $row = $rowStatus[$assessment->id]; @endphp
                            <div wire:key="assessment-{{ $assessment->id }}" class="p-space-lg flex items-center gap-space-md">
                                @if ($row['route'])
                                    <a href="{{ $row['route'] }}" class="flex-1 min-w-0">
                                        <p class="font-label-lg text-label-lg text-on-surface truncate">{{ $assessment->title }}</p>
                                        <p class="text-body-sm text-on-surface-variant mt-1">
                                            Weight {{ rtrim(rtrim(number_format($assessment->weight, 2), '0'), '.') }}%
                                            @if ($assessment->start_date)
                                                &middot; {{ $assessment->start_date->format('M j, Y') }} &ndash; {{ $assessment->end_date?->format('M j, Y') }}
                                            @endif
                                        </p>
                                    </a>
                                @else
                                    <div class="flex-1 min-w-0 opacity-60">
                                        <p class="font-label-lg text-label-lg text-on-surface truncate">{{ $assessment->title }}</p>
                                        <p class="text-body-sm text-on-surface-variant mt-1">Coming soon</p>
                                    </div>
                                @endif

                                <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant flex-shrink-0">
                                    {{ str($row['status'])->replace('_', ' ')->title() }}
                                </span>

                                @unless ($isStudent)
                                    <div class="flex gap-space-sm flex-shrink-0">
                                        @if ($row['route'])
                                            <a
                                                href="{{ route('assessments.edit', $assessment) }}"
                                                class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex"
                                                title="Edit assessment"
                                            >
                                                <span class="material-symbols-outlined">edit</span>
                                            </a>

                                            <button
                                                type="button"
                                                @click="deleteId = @js($assessment->id); deleteName = @js($assessment->title); deleteConfirmText = ''; showDeleteModal = true"
                                                class="p-2 hover:bg-surface-container rounded transition text-error"
                                            >
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                @endunless
                            </div>
                        @endforeach
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
