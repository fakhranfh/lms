@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    <div class="flex items-center gap-space-md">
        <a href="{{ route('gradebook.index', $course) }}" wire:navigate class="text-on-surface-variant hover:text-on-surface transition">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h1 class="font-headline-md text-headline-md text-on-surface">{{ $student->name }}'s Gradebook</h1>
    </div>

    @include('livewire.courses.partials.gradebook-breakdown')
</div>
