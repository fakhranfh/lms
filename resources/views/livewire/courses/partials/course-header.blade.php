<div class="space-y-space-lg">
    <div class="flex items-center justify-between gap-space-md">
        <h1 class="font-headline-md text-headline-md text-on-surface">{{ $course->title }}</h1>
        <a
            href="{{ route('courses.index') }}"
            wire:navigate
            class="flex-shrink-0 px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors inline-flex items-center gap-space-xs"
        >
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back
        </a>
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
</div>
