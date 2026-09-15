@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => $teacher])

    <div class="flex items-start justify-between">
        <h1 class="font-headline-md text-headline-md text-on-surface">Forum Monitoring</h1>
    </div>

    @if ($selectedSession)
        <div>
            <p class="font-headline-sm text-headline-sm text-on-surface">{{ $selectedSession->title }}</p>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-space-xs">
                Minimum {{ $required }} post{{ $required === 1 ? '' : 's' }} required for this session.
            </p>
        </div>

        <div class="flex items-center justify-between gap-space-md flex-wrap">
            <div class="flex items-center gap-space-sm">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="studentSearch"
                    placeholder="Search students..."
                    class="w-56 h-9 px-space-md rounded-lg border border-outline-variant bg-surface text-body-sm text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary/50"
                />

                @if (trim($studentSearch) !== '')
                    <button
                        type="button"
                        wire:click="$set('studentSearch', '')"
                        class="text-body-sm text-primary font-medium hover:underline flex-shrink-0"
                    >
                        Clear filter
                    </button>
                @endif
            </div>
        </div>

        <!-- Skeleton Loading -->
        <div
            wire:loading.class.remove="hidden"
            wire:target="gotoPage,previousPage,nextPage,studentSearch,perPage"
            class="hidden bg-surface border border-outline-variant rounded-lg overflow-hidden animate-pulse"
        >
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

        <div wire:loading.remove wire:target="gotoPage,previousPage,nextPage,studentSearch,perPage" class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
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
                        @forelse ($studentRows as $row)
                            <tr wire:key="forum-monitoring-student-{{ $row['user']->id }}">
                                <td class="px-space-lg py-space-md">
                                    <div class="flex items-center gap-space-sm">
                                        <x-avatar :user="$row['user']" size="8" />
                                        <span class="font-label-md text-label-md text-on-surface">{{ $row['user']->name }}</span>
                                    </div>
                                </td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['threadCount'] }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['commentCount'] }}</td>
                                <td class="px-space-lg py-space-md text-body-sm text-on-surface">{{ $row['totalPosts'] }}</td>
                                <td class="px-space-lg py-space-md">
                                    @if ($row['met'])
                                        <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-success/10 text-success">
                                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                            Met
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-error/10 text-error">
                                            <span class="material-symbols-outlined text-[16px]">cancel</span>
                                            Not Met
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-space-lg py-space-lg text-center text-body-sm text-on-surface-variant">No students found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-ui.pagination-links :paginator="$studentRows" perPageModel="perPage" :perPageOptions="[10, 25, 50, 100]" />
    @else
        <p class="text-body-sm text-on-surface-variant py-space-md">No online sessions yet.</p>
    @endif
</div>
