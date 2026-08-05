@section('title', $course->title)

<div class="space-y-space-lg">
    <div>
        <h1 class="font-headline-md text-headline-md text-on-surface">Syllabus</h1>
        <p class="text-body-sm text-on-surface-variant mt-space-xs">{{ $course->title }}</p>
    </div>

    @if ($teacher)
        <div class="flex items-center gap-space-md">
            @if ($teacher->profile_photo_path)
                <img src="{{ $teacher->profile_photo_path }}" alt="{{ $teacher->name }}" class="w-10 h-10 rounded-full object-cover border border-outline-variant">
            @else
                <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-on-primary font-headline-sm text-headline-sm">
                    {{ strtoupper(substr($teacher->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <p class="font-label-md text-label-md text-on-surface">{{ $teacher->name }}</p>
                <p class="text-body-xs text-on-surface-variant">Teacher</p>
            </div>
        </div>
    @endif

    @include('livewire.courses.partials.course-tabs', ['tabs' => $courseTabs])

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
            'submissionPoints' => $submissionPoints,
            'teachingLearningStrategyPoints' => $teachingLearningStrategyPoints,
            'textbookPoints' => $textbookPoints,
        ])
    @endif
</div>
