<div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
    <div class="flex items-center justify-between mb-space-md flex-wrap gap-space-sm">
        <h2 class="text-title-md font-title-md font-bold text-on-surface">To-Do</h2>

        <div class="flex items-center gap-space-sm">
            <select wire:model.live="todoCourseFilter" class="text-body-sm rounded-lg border border-outline-variant bg-surface px-space-sm py-space-xs">
                <option value="">All Courses</option>
                @foreach($this->enrolledCourses as $course)
                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                @endforeach
            </select>

            <select wire:model.live="todoTypeFilter" class="text-body-sm rounded-lg border border-outline-variant bg-surface px-space-sm py-space-xs">
                <option value="">All Types</option>
                <option value="material">Materials</option>
                <option value="assessment">Assessments</option>
            </select>
        </div>
    </div>

    <!-- Skeleton Loading (shown while filters are in flight) -->
    <div
        wire:loading.delay.class.remove="hidden"
        wire:target="todoCourseFilter,todoTypeFilter"
        class="hidden animate-pulse divide-y divide-outline-variant"
    >
        @for ($i = 0; $i < 4; $i++)
            <div class="py-space-sm flex items-center justify-between gap-space-md">
                <div class="flex items-center gap-space-sm min-w-0 flex-1">
                    <div class="h-[18px] w-[18px] bg-surface-container rounded-full shrink-0"></div>
                    <div class="min-w-0 flex-1 space-y-space-xs">
                        <div class="h-3.5 bg-surface-container rounded w-2/3"></div>
                        <div class="h-3 bg-surface-container rounded w-1/3"></div>
                    </div>
                </div>
                <div class="h-3 bg-surface-container rounded w-16 shrink-0"></div>
            </div>
        @endfor
    </div>

    <div wire:loading.delay.remove wire:target="todoCourseFilter,todoTypeFilter">
        @if($this->todoItems->isEmpty())
            <p class="text-body-sm text-secondary">Nothing left to do &mdash; great job!</p>
        @else
            <ul class="divide-y divide-outline-variant">
                @foreach($this->todoItems as $item)
                    <li class="py-space-sm">
                        <a href="{{ $item['url'] }}"
                           class="flex items-center justify-between gap-space-md hover:opacity-80 transition-opacity duration-150">
                            <div class="flex items-center gap-space-sm min-w-0">
                                <span class="material-symbols-outlined text-[18px] text-secondary shrink-0">
                                    {{ $item['type'] === 'material' ? 'play_circle' : 'assignment' }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-body-md text-on-surface truncate">{{ $item['title'] }}</p>
                                    <p class="text-body-sm text-secondary truncate">{{ $item['course']->title }}</p>
                                </div>
                            </div>
                            <span class="text-label-sm text-secondary shrink-0 uppercase">{{ $item['type'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <x-ui.pagination-links :paginator="$this->todoItems" class="mt-space-md" />
        @endif
    </div>
</div>
