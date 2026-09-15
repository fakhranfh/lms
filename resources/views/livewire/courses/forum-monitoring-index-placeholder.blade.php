@section('title', $course->title)

<div wire:init="loadData" class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="space-y-space-lg animate-pulse">
        <x-ui.skeleton-box class="h-6 w-56" />

        <x-ui.skeleton-box class="h-9 w-56 rounded-lg" />

        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container/50">
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Student</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Threads</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Comments</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Total Posts</th>
                            <th class="px-space-lg py-space-md text-left font-label-md text-label-md text-on-surface-variant">Requirement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="px-space-lg py-space-md">
                                    <div class="flex items-center gap-space-sm">
                                        <x-ui.skeleton-box class="h-8 w-8 rounded-full flex-shrink-0" />
                                        <x-ui.skeleton-box class="h-4 w-32" />
                                    </div>
                                </td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-4 w-8" /></td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-4 w-8" /></td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-4 w-8" /></td>
                                <td class="px-space-lg py-space-md"><x-ui.skeleton-box class="h-6 w-24 rounded-full" /></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
