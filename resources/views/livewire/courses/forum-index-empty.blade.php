@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
        <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">forum</span>
        <p class="text-body-md text-on-surface-variant">No sessions yet. Forums are created per session.</p>
    </div>
</div>
