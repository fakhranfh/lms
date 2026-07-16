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

            <!-- Video URL -->
            <div>
                <label for="videoEmbedUrl" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Video URL <span class="text-secondary text-body-sm">(YouTube or Vimeo - optional)</span>
                </label>
                <input
                    type="url"
                    id="videoEmbedUrl"
                    wire:model="videoEmbedUrl"
                    placeholder="https://youtube.com/watch?v=... or https://vimeo.com/..."
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('videoEmbedUrl') border-error @enderror"
                />
                @error('videoEmbedUrl')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

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
