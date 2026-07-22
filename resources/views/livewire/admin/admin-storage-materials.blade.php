@section('title', 'School Materials')

<div class="space-y-space-lg" x-data="{ deleteId: null, deleteName: null, deleteBytes: null, showDeleteModal: false, previewUrl: null, previewType: null, previewTitle: null, showPreviewModal: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">School Materials</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Browse and manage uploaded materials across schools</p>
        </div>
        <a href="{{ route('admin.storage.dashboard') }}" class="font-label-md text-label-md text-primary hover:underline">
            Back to Storage Overview
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-space-md">
            <div wire:key="school-filter-{{ $schoolId }}">
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">School</label>
                <x-searchable-select
                    model="schoolId"
                    placeholder="All Schools"
                    :options="$this->schoolOptions->map(fn ($school) => ['id' => $school->id, 'label' => $school->name])"
                    :selectedLabel="optional($this->schoolOptions->firstWhere('id', $schoolId))->name"
                />
            </div>
            <div wire:key="course-filter-{{ $schoolId }}-{{ $courseId }}">
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Course</label>
                <x-searchable-select
                    model="courseId"
                    placeholder="All Courses"
                    :disabled="! $schoolId"
                    :options="$this->courseOptions->map(fn ($course) => ['id' => $course->id, 'label' => $course->title])"
                    :selectedLabel="optional($this->courseOptions->firstWhere('id', $courseId))->title"
                />
            </div>
            <div wire:key="module-filter-{{ $courseId }}-{{ $moduleId }}">
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Module</label>
                <x-searchable-select
                    model="moduleId"
                    placeholder="All Modules"
                    :disabled="! $courseId"
                    :options="$this->moduleOptions->map(fn ($module) => ['id' => $module->id, 'label' => $module->title])"
                    :selectedLabel="optional($this->moduleOptions->firstWhere('id', $moduleId))->title"
                />
            </div>
            <div wire:key="lesson-filter-{{ $moduleId }}-{{ $lessonId }}">
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Lesson</label>
                <x-searchable-select
                    model="lessonId"
                    placeholder="All Lessons"
                    :disabled="! $moduleId"
                    :options="$this->lessonOptions->map(fn ($lesson) => ['id' => $lesson->id, 'label' => $lesson->title])"
                    :selectedLabel="optional($this->lessonOptions->firstWhere('id', $lessonId))->title"
                />
            </div>
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-space-xs">Material Name</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by title..."
                    class="w-full h-[44px] px-3 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary transition-colors outline-none"
                />
            </div>
        </div>

        @if ($schoolId || $courseId || $moduleId || $lessonId || $search)
            <div class="mt-space-md">
                <button type="button" wire:click="resetFilters" class="font-label-md text-label-md text-secondary hover:underline">
                    Clear all filters
                </button>
            </div>
        @endif
    </div>

    <x-livewire-data-table
        :columns="[
            ['key' => 'title', 'label' => 'Material'],
            ['key' => 'school', 'label' => 'School / Course / Lesson', 'sortable' => false],
            ['key' => 'file_size', 'label' => 'Size'],
            ['key' => 'created_at', 'label' => 'Uploaded'],
        ]"
        :items="$this->materials"
        :sort="$sort"
        :direction="$direction"
        :perPage="$perPage"
        loadingTarget="schoolId,courseId,moduleId,lessonId,search,perPage,sortBy,resetFilters,deleteMaterial,previousPage,nextPage,gotoPage"
    >
        @forelse ($this->materials as $material)
            <tr wire:key="material-{{ $material->id }}" class="border-b border-outline-variant last:border-0 hover:bg-surface-container-lowest">
                <td class="px-space-lg py-space-md">
                    <div class="flex items-center gap-space-md">
                        {{-- File Preview --}}
                        <button
                            type="button"
                            @click="previewUrl = '{{ $material->file_url }}'; previewType = '{{ $material->type->value }}'; previewTitle = '{{ addslashes($material->title) }}'; showPreviewModal = true"
                            class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-surface-container border border-outline-variant flex items-center justify-center hover:opacity-80 transition"
                            title="Preview {{ $material->title }}"
                        >
                            @switch($material->type->value)
                                @case('Image')
                                    <img src="{{ $material->file_url }}" alt="{{ $material->title }}" class="w-full h-full object-cover" loading="lazy" />
                                    @break
                                @case('Video')
                                    <video src="{{ $material->file_url }}#t=0.1" class="w-full h-full object-cover pointer-events-none" preload="metadata" muted></video>
                                    @break
                                @case('Audio')
                                    <span class="text-2xl">🎵</span>
                                    @break
                                @case('PDF')
                                    <span class="text-2xl">📄</span>
                                    @break
                                @case('Presentation')
                                    <span class="text-2xl">📊</span>
                                    @break
                                @case('Interactive')
                                    <span class="text-2xl">🎮</span>
                                    @break
                                @default
                                    <span class="text-2xl">📝</span>
                            @endswitch
                        </button>
                        <div>
                            <p class="font-body-md text-body-md text-on-surface">{{ $material->title }}</p>
                            <p class="text-body-sm text-on-surface-variant">{{ $material->type->value }} · v{{ $material->version }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">
                    {{ $material->lesson?->module?->course?->school?->name }} /
                    {{ $material->lesson?->module?->course?->title }} /
                    {{ $material->lesson?->title }}
                </td>
                <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $formatBytes($material->file_size) }}</td>
                <td class="px-space-lg py-space-md font-body-md text-body-md text-on-surface">{{ $material->created_at->diffForHumans() }}</td>
                <td class="px-space-lg py-space-md text-right">
                    <button
                        type="button"
                        @click="deleteId = '{{ $material->id }}'; deleteName = '{{ addslashes($material->title) }}'; deleteBytes = '{{ $formatBytes($material->file_size) }}'; showDeleteModal = true"
                        class="font-label-md text-label-md text-error hover:underline"
                    >
                        Delete
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-space-lg py-space-lg text-center text-body-md text-on-surface-variant">No materials match these filters.</td>
            </tr>
        @endforelse
    </x-livewire-data-table>

    {{-- File Preview Modal --}}
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-[60]">
        <div
            @click="showPreviewModal = false"
            class="fixed inset-0 bg-black bg-opacity-70 transition-opacity"
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
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-3xl w-full max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between px-space-lg py-space-md border-b border-outline-variant">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface truncate" x-text="previewTitle"></h3>
                    <button type="button" @click="showPreviewModal = false" class="text-on-surface-variant hover:text-on-surface flex-shrink-0 ml-space-md">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-space-lg overflow-y-auto flex-1 flex items-center justify-center bg-surface-container/40">
                    <template x-if="previewType === 'Image'">
                        <img :src="previewUrl" :alt="previewTitle" class="max-w-full max-h-[65vh] object-contain rounded-lg" />
                    </template>
                    <template x-if="previewType === 'Video'">
                        <video :src="previewUrl" class="max-w-full max-h-[65vh] rounded-lg" controls autoplay></video>
                    </template>
                    <template x-if="previewType === 'Audio'">
                        <audio :src="previewUrl" class="w-full" controls autoplay></audio>
                    </template>
                    <template x-if="previewType === 'PDF'">
                        <iframe :src="previewUrl" class="w-full h-[65vh] rounded-lg border border-outline-variant"></iframe>
                    </template>
                    <template x-if="!['Image', 'Video', 'Audio', 'PDF'].includes(previewType)">
                        <div class="text-center py-space-xl space-y-space-md">
                            <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto">description</span>
                            <p class="text-body-md text-on-surface-variant">Preview isn't available for this file type.</p>
                        </div>
                    </template>
                </div>

                <div class="px-space-lg py-space-md border-t border-outline-variant text-right">
                    <a :href="previewUrl" target="_blank" rel="noopener noreferrer" class="font-label-md text-label-md text-primary hover:underline">
                        Open in new tab
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Material Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[60]">
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
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Delete Material</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            Are you sure you want to delete "<span x-text="deleteName"></span>"? This frees <span x-text="deleteBytes"></span>. This action cannot be undone.
                        </p>
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
                            @click="showDeleteModal = false; $wire.call('deleteMaterial', deleteId)"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
