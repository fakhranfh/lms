@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-tabs', ['course' => $course, 'activeTab' => $tab])

    <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
        <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">construction</span>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-2">{{ $tabLabel }} — Coming Soon</h2>
        <p class="text-body-md text-on-surface-variant">This part of the course is being built and will be available soon.</p>
    </div>
</div>
