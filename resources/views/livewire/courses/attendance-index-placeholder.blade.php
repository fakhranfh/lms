@section('title', $course->title)

<div wire:init="loadData" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="space-y-space-lg animate-pulse">
        <x-ui.skeleton-box class="h-6 w-40" />

        @if ($isStudent)
            <div class="grid grid-cols-3 gap-space-md">
                @for ($i = 0; $i < 3; $i++)
                    <div class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-xs">
                        <x-ui.skeleton-box class="h-3 w-24" />
                        <x-ui.skeleton-box class="h-6 w-12" />
                    </div>
                @endfor
            </div>

            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                <x-attendance.table-skeleton :is-student="true" />
            </div>
        @else
            <div class="flex flex-wrap gap-space-xs border-b border-outline-variant pb-space-sm">
                @for ($i = 0; $i < 3; $i++)
                    <x-ui.skeleton-box class="h-9 w-24" />
                @endfor
            </div>

            <x-ui.skeleton-box class="h-11 w-full max-w-sm rounded-lg" />

            <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
                <x-attendance.table-skeleton :is-student="false" :can-manage="$canManage" />
            </div>
        @endif
    </div>
</div>
