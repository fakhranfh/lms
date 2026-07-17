@section('title', 'Courses')

<div class="space-y-space-lg">
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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Courses</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Manage your courses and content</p>
        </div>
        @can('courses.create')
            <a href="{{ route('courses.create') }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm">
                <span class="material-symbols-outlined">add</span>
                New Course
            </a>
        @endcan
    </div>

    <!-- Search -->
    <div class="flex gap-space-md">
        <div class="flex-1 relative">
            <span class="material-symbols-outlined absolute left-space-lg top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search courses..."
                class="w-full pl-12 pr-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
        </div>
    </div>

    <!-- Skeleton Loading (shown while search is in flight) -->
    <div
        wire:loading.delay.class.remove="hidden"
        wire:target="search"
        class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg animate-pulse"
    >
        @for ($i = 0; $i < 6; $i++)
            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden flex flex-col">
                <div class="p-space-lg border-b border-outline-variant space-y-space-md">
                    <div class="flex items-start justify-between gap-space-md">
                        <div class="h-4 bg-surface-container rounded w-2/3"></div>
                        <div class="h-5 bg-surface-container rounded-full w-16 shrink-0"></div>
                    </div>
                    <div class="space-y-space-xs">
                        <div class="h-3 bg-surface-container rounded w-full"></div>
                        <div class="h-3 bg-surface-container rounded w-4/5"></div>
                    </div>
                </div>
                <div class="px-space-lg py-space-md space-y-space-sm flex-1">
                    <div class="h-3 bg-surface-container rounded w-3/4"></div>
                    <div class="h-3 bg-surface-container rounded w-2/3"></div>
                    <div class="h-3 bg-surface-container rounded w-1/2"></div>
                </div>
                <div class="px-space-lg py-space-md bg-surface-container/50 border-t border-outline-variant flex items-center justify-between">
                    <div class="h-3 bg-surface-container rounded w-20"></div>
                    <div class="flex gap-space-xs">
                        <div class="h-8 w-8 bg-surface-container rounded"></div>
                        <div class="h-8 w-8 bg-surface-container rounded"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <div wire:loading.remove wire:target="search">
    @if ($courses->isEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
            @if ($search)
                <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">search_off</span>
                <p class="text-body-md text-on-surface-variant mb-4">No courses found matching "{{ $search }}"</p>
                <button
                    wire:click="$set('search', '')"
                    type="button"
                    class="text-primary font-medium hover:underline"
                >
                    Clear search
                </button>
            @else
                <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">school</span>
                <p class="text-body-md text-on-surface-variant mb-4">No courses yet. Create your first course to get started.</p>
                @can('courses.create')
                    <a href="{{ route('courses.create') }}" class="text-primary font-medium hover:underline">
                        Create Your First Course
                    </a>
                @endcan
            @endif
        </div>
    @else
        <!-- Courses Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
            @foreach ($courses as $course)
                <div wire:key="course-{{ $course->id }}" class="bg-surface border border-outline-variant rounded-lg overflow-hidden hover:border-primary/50 transition-all hover:shadow-md group flex flex-col">
                    <!-- Card Header -->
                    <div class="p-space-lg border-b border-outline-variant">
                        <div class="flex items-start justify-between gap-space-md mb-space-md">
                            <a href="{{ route('courses.show', $course) }}" class="flex-1">
                                <h3 class="font-label-lg text-label-lg text-on-surface group-hover:text-primary transition-colors line-clamp-2">
                                    {{ $course->title }}
                                </h3>
                            </a>
                            @if ($course->is_published)
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-success/10 border border-success/20 text-success whitespace-nowrap"
                                    @if ($course->published_at) title="Published on {{ $course->published_at->format('M j, Y') }}" @endif
                                >
                                    Published
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant whitespace-nowrap">
                                    Draft
                                </span>
                            @endif
                        </div>

                        @if ($course->is_published && $course->published_at)
                            <p class="text-body-xs text-on-surface-variant mb-space-sm">
                                Published on {{ $course->published_at->format('M j, Y') }}
                            </p>
                        @endif

                        @if ($course->description)
                            <p class="text-body-sm text-on-surface-variant line-clamp-2">
                                {{ $course->description }}
                            </p>
                        @endif
                    </div>

                    <!-- Modules List -->
                    <div class="flex-1 overflow-hidden flex flex-col">
                        @if ($course->modules->isEmpty())
                            <div class="px-space-lg py-space-lg text-center flex-1 flex items-center justify-center">
                                <div>
                                    <span class="material-symbols-outlined text-on-surface-variant text-[32px] block mx-auto mb-2">folder_open</span>
                                    <p class="text-body-sm text-on-surface-variant">No modules yet</p>
                                </div>
                            </div>
                        @else
                            <div class="px-space-lg py-space-md space-y-space-xs max-h-[240px] overflow-y-auto">
                                @foreach ($course->modules as $module)
                                    <div wire:key="module-{{ $module->id }}" class="flex items-start gap-space-md p-space-sm rounded hover:bg-surface-container/50 transition-colors group/item">
                                        <span class="material-symbols-outlined text-on-surface-variant text-[18px] flex-shrink-0 mt-0.5">layers</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-body-sm text-on-surface font-medium line-clamp-1 group-hover/item:text-primary transition-colors">
                                                {{ $module->title }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Card Footer -->
                    <div class="px-space-lg py-space-md bg-surface-container/50 border-t border-outline-variant flex items-center justify-between">
                        <div class="flex items-center gap-space-md text-body-sm text-on-surface-variant">
                            <span class="material-symbols-outlined text-[18px]">folder</span>
                            {{ $course->modules->count() }} module{{ $course->modules->count() !== 1 ? 's' : '' }}
                        </div>

                        <div class="flex gap-space-xs">
                            @can('courses.edit')
                                <a
                                    href="{{ route('courses.edit', $course) }}"
                                    class="p-2 hover:bg-surface rounded transition text-primary"
                                    title="Edit"
                                >
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </a>
                            @endcan
                            @can('courses.delete')
                                <button
                                    type="button"
                                    @click="$dispatch('open-delete-confirm', { id: '{{ $course->id }}', name: @js($course->title), type: 'courses' })"
                                    class="p-2 hover:bg-surface rounded transition text-error"
                                    title="Delete"
                                >
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if ($courses->hasPages())
            <div class="flex items-center justify-between">
                <p class="text-body-sm text-on-surface-variant">
                    Showing {{ $courses->firstItem() }} to {{ $courses->lastItem() }} of {{ $courses->total() }} courses
                </p>
                {{ $courses->links() }}
            </div>
        @endif
    @endif
    </div>
</div>
