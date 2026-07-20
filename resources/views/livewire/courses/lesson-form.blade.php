@section('title', $pageTitle)

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-space-xl">
            <h1 class="font-headline-md text-headline-md text-on-surface">
                {{ $pageTitle }}
            </h1>
            <p class="text-body-md text-on-surface-variant mt-space-sm">
                in <strong>{{ $module->title }}</strong> / {{ $module->course->title }}
            </p>
        </div>

        <!-- Form -->
        <form wire:submit="save" class="space-y-space-lg">
            <!-- Title -->
            <div>
                <label for="title" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Lesson Title <span class="text-error">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    wire:model="title"
                    placeholder="e.g., Introduction to HTML Tags"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('title') border-error @enderror"
                />
                @error('title')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Content
                </label>
                <textarea
                    id="content"
                    wire:model="content"
                    placeholder="Lesson content (supports HTML)..."
                    rows="8"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 font-mono @error('content') border-error @enderror"
                ></textarea>
                <p class="text-body-sm text-on-surface-variant mt-space-sm">
                    You can use HTML tags for formatting. A rich text editor can be integrated later.
                </p>
                @error('content')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Video URL (Backward Compatibility) -->
            <details class="p-space-md bg-surface-container rounded-lg border border-outline">
                <summary class="font-label-md text-on-surface" style="cursor: pointer;">
                    Legacy Video URL (for backward compatibility)
                </summary>
                <div class="mt-space-md">
                    <label for="videoEmbedUrl" class="block text-label-sm text-on-surface mb-space-sm font-label-md">
                        Video URL <span class="text-secondary text-body-sm">(YouTube or Vimeo - optional)</span>
                    </label>
                    <input
                        type="url"
                        id="videoEmbedUrl"
                        wire:model="videoEmbedUrl"
                        placeholder="https://youtube.com/watch?v=... or https://vimeo.com/..."
                        class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('videoEmbedUrl') border-error @enderror"
                    />
                    <p class="text-body-sm text-on-surface-variant mt-space-xs">
                        Deprecated: Use the Materials section below instead of adding videos here.
                    </p>
                    @error('videoEmbedUrl')
                        <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                    @enderror
                </div>
            </details>

            <!-- Materials -->
            @if ($lesson)
                <div>
                    <label class="block text-label-md text-on-surface mb-space-md font-label-md">
                        Lesson Materials
                    </label>

                    @if ($errorMessage)
                        <div class="mb-space-md p-space-md bg-error/10 border border-error text-error rounded-lg text-body-sm">
                            {{ $errorMessage }}
                        </div>
                    @endif

                    <!-- Quota Info -->
                    @if ($quota)
                        <div class="mb-space-md p-space-md bg-surface-container rounded-lg">
                            <div class="flex items-center justify-between mb-space-sm">
                                <span class="text-label-sm text-on-surface">Storage Quota</span>
                                <span class="text-label-sm font-label-md">{{ round($quota['percentage']) }}%</span>
                            </div>
                            <div class="w-full h-2 bg-surface rounded-full overflow-hidden">
                                <div
                                    class="h-full {{ $quota['percentage'] >= 90 ? 'bg-error' : ($quota['percentage'] >= 80 ? 'bg-warning' : 'bg-primary') }} transition-all duration-300"
                                    style="width: {{ $quota['percentage'] }}%"
                                ></div>
                            </div>
                            <p class="text-body-sm text-on-surface-variant mt-space-xs">
                                {{ $this->formatBytes($quota['used']) }} / {{ $this->formatBytes($quota['limit']) }} used globally
                            </p>
                        </div>
                    @endif

                    <!-- Existing Materials -->
                    @if ($materials->count() > 0)
                        <div class="mb-space-md space-y-space-sm">
                            <h4 class="text-label-md text-on-surface font-label-md">Current Materials</h4>
                            <div class="space-y-space-xs">
                                @foreach ($materials as $material)
                                    <div class="flex items-center justify-between p-space-md bg-surface-container rounded-lg">
                                        <div class="flex items-center gap-space-md flex-1">
                                            <span class="text-body-md">{{ $this->getMaterialIcon($material->type) }}</span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-body-sm text-on-surface font-medium truncate">{{ $material->title }}</p>
                                                <p class="text-body-sm text-on-surface-variant">{{ $material->type->value }} • {{ $this->formatBytes($material->file_size) }}</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="deleteMaterial('{{ $material->id }}')"
                                            class="ml-space-md px-space-md py-space-sm text-error hover:bg-error/10 rounded transition"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Upload New Material -->
                    <div class="p-space-lg bg-surface-container rounded-lg border-2 border-dashed border-outline">
                        <div class="text-center">
                            <p class="text-body-md text-on-surface mb-space-md">Upload Lesson Material</p>
                            <input
                                type="file"
                                id="materialFile"
                                wire:model="materialFile"
                                wire:loading.attr="disabled"
                                class="mb-space-md"
                            />
                            <div wire:loading.delay class="text-body-sm text-on-surface-variant">
                                Uploading...
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-space-lg bg-surface-container rounded-lg border border-outline">
                    <p class="text-body-sm text-on-surface-variant">
                        Save the lesson first before adding materials.
                    </p>
                </div>
            @endif

            <!-- Duration -->
            <div>
                <label for="durationMinutes" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Duration <span class="text-secondary text-body-sm">(minutes - optional)</span>
                </label>
                <input
                    type="number"
                    id="durationMinutes"
                    wire:model="durationMinutes"
                    placeholder="e.g., 15"
                    min="1"
                    max="480"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('durationMinutes') border-error @enderror"
                />
                <p class="text-body-sm text-on-surface-variant mt-space-sm">
                    Estimated reading/viewing time for this lesson
                </p>
                @error('durationMinutes')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Publish Status -->
            <div class="flex items-center gap-space-md p-space-lg bg-surface-container rounded-lg">
                <div>
                    <input
                        type="checkbox"
                        id="isPublished"
                        wire:model="isPublished"
                        class="rounded"
                    />
                </div>
                <div class="flex-1">
                    <label for="isPublished" class="block text-label-md text-on-surface font-label-md cursor-pointer">
                        Publish Lesson
                    </label>
                    <p class="text-body-sm text-on-surface-variant mt-space-xs">
                        Published lessons are visible to students. Unpublished lessons remain in draft mode.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-space-md pt-space-lg">
                <a
                    href="{{ route('courses.show', $module->course) }}"
                    class="flex-1 px-space-lg py-space-md border border-outline rounded-lg font-label-md text-label-md text-on-surface text-center hover:bg-surface-container transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                >
                    {{ $lesson ? 'Update Lesson' : 'Create Lesson' }}
                </button>
            </div>
        </form>
    </div>
</div>
