@section('title', $pageTitle)

<div class="min-h-screen bg-background py-space-xl px-gutter">
    <div class="w-full">
        <!-- Breadcrumb -->
        <div class="mb-space-lg">
            <nav class="flex items-center gap-space-sm text-body-sm text-on-surface-variant">
                <a href="{{ route('dashboard') }}" class="hover:text-on-surface transition">Courses</a>
                <span>/</span>
                <a href="#" class="hover:text-on-surface transition">{{ $course->title }}</a>
                <span>/</span>
                <a href="#" class="hover:text-on-surface transition">{{ $module->title }}</a>
                <span>/</span>
                <span class="text-on-surface font-medium">{{ $lesson->title }}</span>
            </nav>
        </div>

        <!-- Header -->
        <div class="mb-space-xl">
            <div class="flex items-start justify-between gap-space-lg">
                <div class="flex-1">
                    <h1 class="font-headline-md text-headline-md text-on-surface">
                        {{ $lesson->title }}
                    </h1>
                    <p class="text-body-md text-on-surface-variant mt-space-sm">
                        {{ $module->title }} • Lesson {{ $currentLessonIndex }} of {{ $totalLessonsInModule }}
                    </p>
                </div>
                @if ($lesson->duration_minutes)
                    <div class="text-body-sm text-on-surface-variant whitespace-nowrap">
                        ⏱ {{ $lesson->duration_minutes }} min
                    </div>
                @endif
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-space-xl">
            <div class="flex items-center justify-between mb-space-sm">
                <span class="text-label-sm text-on-surface-variant font-label-md">Module Progress</span>
                <span class="text-label-sm text-on-surface font-label-md">{{ $completedLessonsInModule }} / {{ $totalLessonsInModule }} lessons</span>
            </div>
            <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                <div
                    class="h-full bg-primary transition-all duration-300"
                    style="width: {{ $completionPercentage }}%"
                ></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-space-lg">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Video Section -->
                @if ($lesson->video_embed_url)
                    <div class="mb-space-xl">
                        <div class="bg-surface-container rounded-lg overflow-hidden aspect-video flex items-center justify-center">
                            <iframe
                                width="100%"
                                height="100%"
                                src="{{ $lesson->video_embed_url }}"
                                title="{{ $lesson->title }}"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </div>
                    </div>
                @endif

                <!-- Content -->
                <div class="mb-space-xl bg-surface-container rounded-lg p-space-lg">
                    <div class="prose prose-sm max-w-none text-on-surface">
                        {!! nl2br(e($lesson->content)) !!}
                    </div>
                </div>

                <!-- Mark Complete Button -->
                @auth
                    <div class="mb-space-xl">
                        @if (!$isCompleted)
                            <button
                                wire:click="markComplete"
                                wire:loading.attr="disabled"
                                class="w-full px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity flex items-center justify-center gap-space-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove>✓</span>
                                <span wire:loading class="inline-block animate-spin">⟳</span>
                                <span wire:loading.remove>Mark as Complete</span>
                                <span wire:loading>Marking...</span>
                            </button>
                        @else
                            <div class="w-full px-space-lg py-space-md bg-primary/10 text-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-space-sm">
                                <span>✓</span>
                                <span>Lesson Complete</span>
                            </div>
                        @endif
                    </div>
                @endauth

                <!-- Navigation -->
                <div class="flex gap-space-md">
                    @if ($previousLesson)
                        <a
                            href="{{ route('lessons.show', $previousLesson) }}"
                            class="flex-1 px-space-lg py-space-md border border-outline rounded-lg font-label-md text-label-md text-on-surface text-center hover:bg-surface-container transition"
                        >
                            ← Previous Lesson
                        </a>
                    @else
                        <div class="flex-1"></div>
                    @endif

                    @if ($nextLesson)
                        <a
                            href="{{ route('lessons.show', $nextLesson) }}"
                            class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md text-center hover:opacity-90 transition-opacity"
                        >
                            Next Lesson →
                        </a>
                    @else
                        <a
                            href="{{ route('courses.show', $course) }}"
                            class="flex-1 px-space-lg py-space-md bg-primary text-on-primary rounded-lg font-label-md text-label-md text-center hover:opacity-90 transition-opacity"
                        >
                            Back to Course
                        </a>
                    @endif
                </div>
            </div>

            <!-- Sidebar: Course Outline -->
            <div class="lg:col-span-1">
                <div class="bg-surface-container rounded-lg p-space-lg sticky top-space-lg">
                    <h3 class="font-label-lg text-label-lg text-on-surface mb-space-md">
                        {{ $course->title }}
                    </h3>

                    <div class="space-y-space-md max-h-96 overflow-y-auto">
                        @foreach ($course->modules()->where('is_published', true)->orderBy('order')->get() as $mod)
                            <div class="space-y-space-xs">
                                <!-- Module Title -->
                                <div class="px-space-md py-space-sm rounded-lg bg-surface text-on-surface-variant">
                                    <p class="text-label-sm font-label-md">{{ $mod->title }}</p>
                                </div>

                                <!-- Lessons in Module -->
                                <div class="space-y-space-xs pl-space-md">
                                    @foreach ($mod->lessons()->where('is_published', true)->orderBy('order')->get() as $item)
                                        <a
                                            href="{{ route('lessons.show', $item) }}"
                                            class="block px-space-md py-space-sm rounded-lg transition @if ($item->id === $lesson->id) bg-primary/10 text-primary font-medium @else text-on-surface hover:bg-surface @endif"
                                        >
                                            <div class="flex items-start gap-space-sm">
                                                <span class="text-body-sm font-medium flex-shrink-0 pt-space-xs">
                                                    @if (auth()->check() && $item->isCompletedBy(auth()->user()))
                                                        ✓
                                                    @else
                                                        •
                                                    @endif
                                                </span>
                                                <span class="text-body-sm break-words">{{ $item->title }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
