@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-tabs', ['tabs' => $courseTabs])

    <div class="flex items-start justify-between">
        <div>
            <h1 class="font-headline-md text-headline-md text-on-surface">Syllabus</h1>
            <p class="text-body-sm text-on-surface-variant mt-space-xs">{{ $course->title }}</p>
        </div>

        @if ($canEdit && $syllabus)
            <a
                href="{{ route('syllabus.edit', $course) }}"
                class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity flex-shrink-0"
            >
                Edit Syllabus
            </a>
        @endif
    </div>

    @if (! $syllabus)
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center space-y-space-md">
            <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto">menu_book</span>
            <p class="text-body-md text-on-surface-variant">No syllabus has been created for this course yet.</p>
            @if ($canEdit)
                <a
                    href="{{ route('syllabus.edit', $course) }}"
                    class="inline-block px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                >
                    Create Syllabus
                </a>
            @endif
        </div>
    @else
        @include('livewire.courses.partials.syllabus-sections-readonly', [
            'syllabus' => $syllabus,
            'classPoliciesByScope' => $classPoliciesByScope,
            'evaluationsWithTotals' => $evaluationsWithTotals,
            'submissionPoints' => $submissionPoints,
            'teachingLearningStrategyPoints' => $teachingLearningStrategyPoints,
            'textbookPoints' => $textbookPoints,
        ])
    @endif
</div>
