@section('title', $isStudent ? $course->title : 'Course Builder')

<div class="space-y-space-lg" x-data="{ deleteType: null, deleteId: null, deleteName: null, showDeleteModal: false, deleteConfirmText: '' }">
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
            <h1 class="font-headline-md text-headline-md text-on-surface">{{ $course->title }}</h1>
            <p class="text-body-sm text-on-surface-variant mt-1 flex items-center gap-space-sm">
                @if ($course->is_published)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-success/10 border border-success/20 text-success">
                        Published
                    </span>
                    @if ($course->published_at)
                        <span class="text-body-xs text-on-surface-variant">
                            on {{ $course->published_at->format('M j, Y') }}
                        </span>
                    @endif
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">
                        Draft
                    </span>
                @endif
            </p>
        </div>

        @unless ($isStudent)
            <div class="flex gap-space-md">
                <a
                    href="{{ route('modules.create', $course) }}"
                    class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm"
                >
                    <span class="material-symbols-outlined">add</span>
                    Add Module
                </a>
            </div>
        @endunless
    </div>

    <!-- Course Description -->
    @if ($course->description)
        <div class="bg-surface-container rounded-lg p-space-lg">
            <p class="text-body-md text-on-surface">{{ $course->description }}</p>
        </div>
    @endif

    <!-- Modules List -->
    @if ($modules->isEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
            <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">folder_open</span>
            <p class="text-body-md text-on-surface-variant mb-4">No modules yet. Create one to get started.</p>
            <a
                href="{{ route('modules.create', $course) }}"
                class="text-primary font-medium hover:underline"
            >
                Add First Module
            </a>
        </div>
    @else
        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="space-y-0">
                @foreach ($modules as $module)
                    <div
                        wire:key="module-{{ $module->id }}"
                        class="border-b border-outline-variant last:border-0"
                        x-data="{
                            open: @js($expandedModules[$module->id] ?? false),
                            loaded: @js($expandedModules[$module->id] ?? false),
                        }"
                    >
                        <!-- Module Header -->
                        <div class="p-space-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-space-md flex-1">
                                    <button
                                        type="button"
                                        @click="open = !open; if (!loaded) { loaded = true; $wire.call('toggleModule', '{{ $module->id }}') }"
                                        class="p-2 hover:bg-surface-container rounded transition"
                                    >
                                        <span class="material-symbols-outlined transition-transform" :class="open ? 'rotate-90' : ''">
                                            chevron_right
                                        </span>
                                    </button>

                                    <div class="flex-1">
                                        <div class="flex items-center gap-space-md">
                                            <h3 class="font-label-lg text-label-lg text-on-surface">{{ $module->title }}</h3>
                                            @if ($module->is_published)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-success/10 border border-success/20 text-success">
                                                    Published
                                                </span>
                                                @if ($module->published_at)
                                                    <span class="text-body-xs text-on-surface-variant">
                                                        on {{ $module->published_at->format('M j, Y') }}
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                        @if ($module->description)
                                            <p class="text-body-sm text-on-surface-variant mt-1">{{ $module->description }}</p>
                                        @endif
                                        <p class="text-body-sm text-secondary mt-2">
                                            {{ $module->lessonsCount() }} lesson{{ $module->lessonsCount() !== 1 ? 's' : '' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Module Actions -->
                                @unless ($isStudent)
                                    <div class="flex gap-space-sm ml-auto">
                                        @if ($module->course->modules->count() > 1 && $module->order > 1)
                                            <button
                                                type="button"
                                                wire:click="moveModuleUp('{{ $module->id }}')"
                                                title="Move up"
                                                class="p-2 hover:bg-surface-container rounded transition"
                                            >
                                                <span class="material-symbols-outlined">arrow_upward</span>
                                            </button>
                                        @endif

                                        @if ($module->course->modules->count() > 1 && $module->order < $module->course->modules->count())
                                            <button
                                                type="button"
                                                wire:click="moveModuleDown('{{ $module->id }}')"
                                                title="Move down"
                                                class="p-2 hover:bg-surface-container rounded transition"
                                            >
                                                <span class="material-symbols-outlined">arrow_downward</span>
                                            </button>
                                        @endif

                                        <a
                                            href="{{ route('modules.edit', $module) }}"
                                            class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex"
                                            title="Edit module"
                                        >
                                            <span class="material-symbols-outlined">edit</span>
                                        </a>

                                        <button
                                            type="button"
                                            @click="deleteType = 'modules'; deleteId = @js($module->id); deleteName = @js($module->title); deleteConfirmText = ''; showDeleteModal = true"
                                            class="p-2 hover:bg-surface-container rounded transition text-error"
                                        >
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                @endunless
                            </div>
                        </div>

                        <!-- Lessons (Expandable) -->
                        <div x-show="open" class="w-full bg-surface-container/50 border-t border-outline-variant">
                            @unless ($expandedModules[$module->id] ?? false)
                                <div wire:loading wire:target="toggleModule('{{ $module->id }}')" class="w-full space-y-0">
                                    @for ($i = 0; $i < 3; $i++)
                                        <div class="w-full p-space-lg border-t border-outline-variant first:border-0 flex items-center justify-between gap-space-md">
                                            <div class="flex items-center gap-space-md flex-1 min-w-0">
                                                <div class="w-8 text-center flex-shrink-0">
                                                    <div class="h-4 bg-gray-300 rounded w-6 animate-pulse"></div>
                                                </div>
                                                <div class="flex-1 min-w-0 space-y-2">
                                                    <div class="h-5 bg-gray-300 rounded w-full animate-pulse"></div>
                                                    <div class="h-4 bg-gray-300 rounded w-1/3 animate-pulse"></div>
                                                </div>
                                            </div>
                                            <div class="flex gap-space-sm flex-shrink-0">
                                                <div class="w-10 h-10 bg-gray-300 rounded animate-pulse"></div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            @endunless

                            @if ($expandedModules[$module->id] ?? false)
                                @if ($module->lessons->isEmpty())
                                    <div class="p-space-lg text-center">
                                        <p class="text-body-sm text-on-surface-variant mb-space-md">No lessons in this module</p>
                                        @unless ($isStudent)
                                            <a
                                                href="{{ route('lessons.create', $module) }}"
                                                class="text-primary font-medium text-body-sm hover:underline"
                                            >
                                                Add First Lesson
                                            </a>
                                        @endunless
                                    </div>
                                @else
                                    <div class="space-y-0">
                                        @foreach ($module->lessons as $lesson)
                                            <div wire:key="lesson-{{ $lesson->id }}" class="p-space-lg border-t border-outline-variant first:border-0 flex items-center justify-between group">
                                                <div class="flex items-center gap-space-md flex-1">
                                                    <div class="w-8 text-center">
                                                        <span class="text-body-sm text-secondary font-medium">{{ $lesson->order }}</span>
                                                    </div>

                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-space-md">
                                                            <h4 class="font-body-md text-body-md text-on-surface">{{ $lesson->title }}</h4>
                                                            @if ($lesson->is_published)
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-success/10 border border-success/20 text-success">
                                                                    Published
                                                                </span>
                                                                @if ($lesson->published_at)
                                                                    <span class="text-body-xs text-on-surface-variant">
                                                                        on {{ $lesson->published_at->format('M j, Y') }}
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                        @if ($lesson->duration_minutes)
                                                            <p class="text-body-sm text-secondary mt-1">
                                                                <span class="material-symbols-outlined inline-block text-[16px] align-text-bottom">schedule</span>
                                                                {{ $lesson->duration_minutes }} min
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Lesson Actions -->
                                                @if ($isStudent)
                                                    @if ($lesson->is_published)
                                                        <a
                                                            href="{{ route('lessons.show', $lesson) }}"
                                                            class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex"
                                                            title="View lesson"
                                                        >
                                                            <span class="material-symbols-outlined">play_circle</span>
                                                        </a>
                                                    @endif
                                                @else
                                                    <div class="flex gap-space-sm ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                                                        @if ($module->lessons->count() > 1 && $lesson->order > 1)
                                                            <button
                                                                type="button"
                                                                wire:click="moveLessonUp('{{ $lesson->id }}')"
                                                                title="Move up"
                                                                class="p-2 hover:bg-surface-container rounded transition"
                                                            >
                                                                <span class="material-symbols-outlined">arrow_upward</span>
                                                            </button>
                                                        @endif

                                                        @if ($module->lessons->count() > 1 && $lesson->order < $module->lessons->count())
                                                            <button
                                                                type="button"
                                                                wire:click="moveLessonDown('{{ $lesson->id }}')"
                                                                title="Move down"
                                                                class="p-2 hover:bg-surface-container rounded transition"
                                                            >
                                                                <span class="material-symbols-outlined">arrow_downward</span>
                                                            </button>
                                                        @endif

                                                        <a
                                                            href="{{ route('lessons.edit', $lesson) }}"
                                                            class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex"
                                                            title="Edit lesson"
                                                        >
                                                            <span class="material-symbols-outlined">edit</span>
                                                        </a>

                                                        <button
                                                            type="button"
                                                            @click="deleteType = 'lessons'; deleteId = @js($lesson->id); deleteName = @js($lesson->title); deleteConfirmText = ''; showDeleteModal = true"
                                                            class="p-2 hover:bg-surface-container rounded transition text-error"
                                                        >
                                                            <span class="material-symbols-outlined">delete</span>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>

                                        @endforeach

                                        @unless ($isStudent)
                                            <div class="px-space-lg py-space-md border-t border-outline-variant">
                                                <a
                                                    href="{{ route('lessons.create', $module) }}"
                                                    class="block w-full py-space-md text-center text-primary text-body-sm font-medium hover:bg-surface-container transition rounded"
                                                >
                                                    + Add Lesson
                                                </a>
                                            </div>
                                        @endunless
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <div
        x-show="showDeleteModal"
        x-cloak
        class="fixed inset-0 z-50"
    >
        <!-- Overlay -->
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

        <!-- Modal -->
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
                            Are you sure you want to delete "<span class="font-medium" x-text="deleteName ?? 'this item'"></span>"?
                            This action cannot be undone.
                        </p>
                        <p x-show="deleteType === 'modules'" class="font-body-sm text-body-sm text-error">
                            All lessons in this module will also be deleted.
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
                            @click="showDeleteModal = false; $wire.call('confirmDelete', deleteType, deleteId)"
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
