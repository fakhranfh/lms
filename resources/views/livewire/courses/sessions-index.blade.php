@section('title', $course->title)

<div class="space-y-space-lg" x-data="{ deleteId: null, deleteName: null, showDeleteModal: false, deleteConfirmText: '' }">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="font-headline-md text-headline-md text-on-surface">Sessions</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">{{ $sessions->count() }} session{{ $sessions->count() !== 1 ? 's' : '' }}</p>
        </div>

        @unless ($isStudent)
            <a
                href="{{ route('sessions.create', $course) }}"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
            >
                <span class="material-symbols-outlined">add</span>
                Add Session
            </a>
        @endunless
    </div>

    <!-- Sessions List -->
    @if ($sessions->isEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
            <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">calendar_month</span>
            <p class="text-body-md text-on-surface-variant mb-4">No sessions yet.</p>
            @unless ($isStudent)
                <a href="{{ route('sessions.create', $course) }}" class="text-primary font-medium hover:underline">
                    Add First Session
                </a>
            @endunless
        </div>
    @else
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="space-y-0">
                @foreach ($sessions as $session)
                    <div wire:key="session-{{ $session->id }}" class="border-b border-outline-variant last:border-0" x-data="{ open: @js($expandedSessions[$session->id] ?? false) }">
                        <!-- Session Header -->
                        <div class="p-space-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-space-md flex-1">
                                    <button
                                        type="button"
                                        @click="open = !open; $wire.call('toggleSession', '{{ $session->id }}')"
                                        class="p-2 hover:bg-surface-container rounded transition"
                                    >
                                        <span class="material-symbols-outlined transition-transform" :class="open ? 'rotate-90' : ''">
                                            chevron_right
                                        </span>
                                    </button>

                                    <div class="flex-1">
                                        <div class="flex items-center gap-space-md">
                                            <h3 class="font-label-lg text-label-lg text-on-surface">{{ $session->title }}</h3>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">
                                                {{ str($session->delivery_mode->value)->replace('_', ' ')->title() }}
                                            </span>
                                        </div>
                                        <p class="text-body-sm text-on-surface-variant mt-1">
                                            {{ $session->date_start->format('M j, Y') }} &ndash; {{ $session->date_end->format('M j, Y') }}
                                        </p>
                                    </div>
                                </div>

                                @unless ($isStudent)
                                    <div class="flex gap-space-sm ml-auto">
                                        <a
                                            href="{{ route('sessions.edit', $session) }}"
                                            class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex"
                                            title="Edit session"
                                        >
                                            <span class="material-symbols-outlined">edit</span>
                                        </a>

                                        <button
                                            type="button"
                                            @click="deleteId = @js($session->id); deleteName = @js($session->title); deleteConfirmText = ''; showDeleteModal = true"
                                            class="p-2 hover:bg-surface-container rounded transition text-error"
                                        >
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                @endunless
                            </div>
                        </div>

                        <!-- Detail (Expandable) -->
                        <div x-show="open" class="w-full bg-surface-container/50 border-t border-outline-variant p-space-lg space-y-space-lg">
                            @if ($session->learning_outcome)
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-space-xs">Learning Outcome</h4>
                                    <p class="text-body-sm text-on-surface-variant">{{ $session->learning_outcome }}</p>
                                </div>
                            @endif

                            @if ($session->subtopics->isNotEmpty())
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-space-xs">Subtopics</h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach ($session->subtopics as $subtopic)
                                            <li class="text-body-sm text-on-surface-variant">{{ $subtopic->subtopic }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($session->materials->isNotEmpty())
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-space-xs">Learning Materials</h4>
                                    <ul class="space-y-1">
                                        @foreach ($session->materials as $material)
                                            <li class="text-body-sm text-on-surface-variant flex items-center gap-space-xs">
                                                <span class="material-symbols-outlined text-[16px]">description</span>
                                                {{ $material->title }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($session->videoConferences->isNotEmpty())
                                <div>
                                    <h4 class="font-label-md text-label-md text-on-surface mb-space-xs">Video Conferences</h4>
                                    <ul class="space-y-2">
                                        @foreach ($session->videoConferences as $videoConference)
                                            <li class="flex items-center justify-between text-body-sm">
                                                <div>
                                                    <span class="text-on-surface">{{ $videoConference->title ?? 'Meeting' }}</span>
                                                    <span class="text-on-surface-variant ml-space-sm">
                                                        {{ $videoConference->scheduled_start_at->format('M j, Y g:i A') }}
                                                    </span>
                                                </div>
                                                @if ($videoConference->meeting_url)
                                                    <a href="{{ $videoConference->meeting_url }}" target="_blank" class="text-primary font-medium hover:underline">
                                                        Join
                                                    </a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (! $session->learning_outcome && $session->subtopics->isEmpty() && $session->materials->isEmpty() && $session->videoConferences->isEmpty())
                                <p class="text-body-sm text-on-surface-variant">No additional details for this session.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showDeleteModal = false"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div
            class="fixed inset-0 flex items-center justify-center p-4"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
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
                            Are you sure you want to delete "<span class="font-medium" x-text="deleteName ?? 'this session'"></span>"?
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
                            @click="showDeleteModal = false; $wire.call('confirmDelete', deleteId)"
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
