@section('title', $course->title)

<div wire:init="loadAssessments" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="space-y-space-lg">
        <div class="flex items-start justify-between">
            <x-ui.skeleton-box class="h-6 w-40" />
            <x-ui.skeleton-box class="h-9 w-36 rounded-lg" />
        </div>

        @for ($group = 0; $group < 2; $group++)
            <div class="space-y-0">
                <div class="flex items-center gap-space-md px-space-lg py-space-md bg-surface border border-outline-variant rounded-t-lg">
                    <x-ui.skeleton-box class="h-5 w-5 rounded" />
                    <x-ui.skeleton-box class="h-4 w-48" />
                </div>
                <div class="bg-surface border border-t-0 border-outline-variant rounded-b-lg overflow-hidden">
                    <x-assessments.table-skeleton :is-student="$isStudent" />
                </div>
            </div>
        @endfor
    </div>
</div>
