@section('title', $pageTitle)

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="max-w-2xl mx-auto">
        <!-- Breadcrumb -->
        <div class="mb-space-lg">
            <nav class="flex items-center gap-space-sm text-body-sm text-on-surface-variant">
                <a href="{{ route('courses.index') }}" class="hover:text-on-surface transition">Courses</a>
                <span>/</span>
                <a href="{{ route('courses.show', $course) }}" class="hover:text-on-surface transition">{{ $course->title }}</a>
                <span>/</span>
                <span class="text-on-surface font-medium">{{ $pageTitle }}</span>
            </nav>
        </div>

        <!-- Header -->
        <div class="mb-space-xl flex items-center justify-between">
            <div>
                <h1 class="font-headline-md text-headline-md text-on-surface">
                    {{ $pageTitle }}
                </h1>
                <p class="text-body-md text-on-surface-variant mt-space-sm">
                    in <strong>{{ $course->title }}</strong>
                </p>
            </div>
            <a
                href="{{ route('courses.show', $course) }}"
                class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors flex-shrink-0"
            >
                Back to Course
            </a>
        </div>

        <!-- Form -->
        <form wire:submit="save" class="space-y-space-lg">
            <!-- Title -->
            <div>
                <label for="title" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Module Title <span class="text-error">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    wire:model="title"
                    placeholder="e.g., HTML Basics"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('title') border-error @enderror"
                />
                @error('title')
                    <p class="text-body-sm text-error mt-space-sm">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Description
                </label>
                <textarea
                    id="description"
                    wire:model="description"
                    placeholder="Brief description of this module..."
                    rows="4"
                    class="w-full px-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 @error('description') border-error @enderror"
                ></textarea>
                @error('description')
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
                        Publish Module
                    </label>
                    <p class="text-body-sm text-on-surface-variant mt-space-xs">
                        Published modules are visible to students. Unpublished modules remain in draft mode.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-space-md pt-space-lg">
                <a
                    href="{{ route('courses.show', $course) }}"
                    class="flex-1 px-space-lg py-space-md border border-outline rounded-lg font-label-md text-label-md text-on-surface text-center hover:bg-surface-container transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                >
                    {{ $module ? 'Update Module' : 'Create Module' }}
                </button>
            </div>
        </form>
    </div>
</div>
