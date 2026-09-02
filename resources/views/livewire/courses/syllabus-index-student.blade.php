@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    @if (! $syllabus)
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center space-y-space-md">
            <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto">menu_book</span>
            <p class="text-body-md text-on-surface-variant">The syllabus for this course has not been published yet.</p>
        </div>
    @else
        @include('livewire.courses.partials.syllabus-sections-readonly', [
            'syllabus' => $syllabus,
            'classPoliciesByScope' => $classPoliciesByScope,
            'evaluationsWithTotals' => $evaluationsWithTotals,
        ])
    @endif
</div>
