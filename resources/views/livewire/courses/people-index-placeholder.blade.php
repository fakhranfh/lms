@section('title', $course->title)

<div wire:init="loadData" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    <div class="space-y-space-lg animate-pulse">
        <div class="flex w-full sm:w-1/4 bg-surface border border-outline-variant rounded-lg overflow-hidden divide-x divide-outline-variant">
            @for ($i = 0; $i < 3; $i++)
                <div class="flex-1 p-space-md">
                    <div class="h-6 bg-surface-container rounded w-8 mx-auto"></div>
                    <div class="h-3 bg-surface-container rounded w-12 mx-auto mt-space-xs"></div>
                </div>
            @endfor
        </div>

        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            @if ($activeSubTab === 'groups')
                <div class="w-full p-space-lg space-y-space-md">
                    <div>
                        <div class="h-5 bg-surface-container rounded w-32"></div>
                    </div>
                    @for ($i = 0; $i < 2; $i++)
                        <div class="border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                            <div class="h-4 bg-surface-container rounded w-24"></div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md">
                                @for ($j = 0; $j < 4; $j++)
                                    <div class="flex items-center gap-space-sm">
                                        <div class="w-8 h-8 rounded-full bg-surface-container flex-shrink-0"></div>
                                        <div class="h-4 bg-surface-container rounded w-2/3"></div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endfor
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-px bg-outline-variant">
                    @for ($i = 0; $i < 9; $i++)
                        <div class="bg-surface p-space-lg flex flex-col items-center gap-space-sm">
                            <div class="w-12 h-12 rounded-full bg-surface-container"></div>
                            <div class="h-4 bg-surface-container rounded w-2/3"></div>
                        </div>
                    @endfor
                </div>
            @endif
        </div>
    </div>
</div>
