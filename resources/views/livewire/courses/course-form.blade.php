@section('title', $course ? 'Edit Course' : 'Create Course')

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-space-xl">
            <h1 class="font-headline-md text-headline-md text-on-surface">
                {{ $course ? 'Edit Course' : 'Create New Course' }}
            </h1>
            <p class="text-body-md text-on-surface-variant mt-space-sm">
                {{ $course ? 'Update course details and settings.' : 'Create a new course for your students.' }}
            </p>

            @if (app()->environment('local'))
                <button
                    type="button"
                    x-data
                    @click="
                        const presets = [
                            { title: 'Introduction to Web Development', description: 'Learn the fundamentals of HTML, CSS, and JavaScript to build responsive, interactive websites from scratch.' },
                            { title: 'Data Structures and Algorithms', description: 'Master core data structures and algorithmic techniques used to solve real-world programming problems efficiently.' },
                            { title: 'Database Design and SQL', description: 'Understand relational database concepts, normalization, and how to write effective SQL queries for data management.' },
                            { title: 'Introduction to Machine Learning', description: 'Explore the basics of supervised and unsupervised learning, model evaluation, and practical applications of ML.' },
                            { title: 'Mobile App Development with Flutter', description: 'Build cross-platform mobile applications using Flutter and Dart, from UI design to app store deployment.' },
                            { title: 'Digital Marketing Fundamentals', description: 'Learn SEO, social media marketing, and content strategy to grow an audience and drive engagement online.' },
                            { title: 'Business Communication Skills', description: 'Develop effective written and verbal communication skills for professional presentations, emails, and meetings.' },
                            { title: 'Financial Accounting Basics', description: 'Understand core accounting principles, financial statements, and how businesses track and report their finances.' },
                        ];
                        const preset = presets[Math.floor(Math.random() * presets.length)];
                        $wire.title = preset.title;
                        $wire.description = preset.description;
                    "
                    class="mt-space-md px-space-md py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition"
                >
                    Dev: Auto-fill
                </button>
            @endif
        </div>

        <!-- Form -->
        <form
            wire:submit="save"
            x-data="{ submitting: false }"
            @submit="submitting = true"
            @course-form-error.window="submitting = false"
            class="space-y-space-lg"
        >
            <!-- Title -->
            <div>
                <label for="title" class="block text-label-md text-on-surface mb-space-sm font-label-md">
                    Course Title <span class="text-error">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    wire:model="title"
                    placeholder="e.g., Introduction to Web Development"
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
                    placeholder="Add a brief description of what students will learn..."
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
                        Publish Course
                    </label>
                    <p class="text-body-sm text-on-surface-variant mt-space-xs">
                        Published courses are visible to students. Unpublished courses remain in draft mode.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-space-md pt-space-lg">
                <a
                    href="{{ $course ? route('courses.show', $course) : route('courses.index') }}"
                    class="flex-1 px-space-lg py-space-md border border-outline rounded-lg font-label-md text-label-md text-on-surface text-center hover:bg-surface-container transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    :disabled="submitting"
                    class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-60 inline-flex items-center justify-center gap-space-sm"
                >
                    <span wire:loading wire:target="save" class="inline-block animate-spin">⟳</span>
                    <span wire:loading.remove wire:target="save">{{ $course ? 'Update Course' : 'Create Course' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>
