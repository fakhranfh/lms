@section('title', 'Assignments')

<div class="space-y-space-lg">
    @if ($successMessage)
        <div class="px-gutter py-space-md bg-success/10 border border-success/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-success text-[20px]" data-weight="fill">check_circle</span>
            <p class="font-body-md text-body-md text-success">{{ $successMessage }}</p>
        </div>
    @endif

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Assignments</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Manage assignments across your courses</p>
        </div>
    </div>

    <div class="flex gap-space-md">
        <div class="flex-1 relative">
            <span class="material-symbols-outlined absolute left-space-lg top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search assignments..."
                class="w-full pl-12 pr-space-lg py-space-md border border-outline rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
        </div>
    </div>

    @if ($assignments->isEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center">
            <span class="material-symbols-outlined text-on-surface-variant text-[48px] block mx-auto mb-4">assignment</span>
            <p class="text-body-md text-on-surface-variant mb-4">
                @if ($search)
                    No assignments found matching "{{ $search }}"
                @else
                    No assignments yet. Create one from a lesson's edit page.
                @endif
            </p>
        </div>
    @else
        <div class="overflow-x-auto border border-outline rounded-lg">
            <table class="w-full text-left">
                <thead class="bg-surface-container text-label-sm text-on-surface-variant">
                    <tr>
                        <th class="px-space-md py-space-sm">Title</th>
                        <th class="px-space-md py-space-sm">Lesson</th>
                        <th class="px-space-md py-space-sm">Status</th>
                        <th class="px-space-md py-space-sm">Submissions</th>
                        <th class="px-space-md py-space-sm">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assignments as $assignment)
                        <tr wire:key="assignment-{{ $assignment->id }}" class="border-t border-outline/30">
                            <td class="px-space-md py-space-sm text-body-sm font-medium text-on-surface">{{ $assignment->title }}</td>
                            <td class="px-space-md py-space-sm text-body-sm text-on-surface-variant">{{ $assignment->lesson->title }}</td>
                            <td class="px-space-md py-space-sm text-body-sm">
                                @if ($assignment->is_published)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-success/10 border border-success/20 text-success">Published</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-body-xs font-medium bg-surface-container text-on-surface-variant">Draft</span>
                                @endif
                            </td>
                            <td class="px-space-md py-space-sm text-body-sm">{{ $assignment->submissions_count }}</td>
                            <td class="px-space-md py-space-sm text-body-sm space-x-space-sm">
                                @can('assignments.edit')
                                    <a href="{{ route('assignments.edit', $assignment) }}" class="text-primary hover:underline">Edit</a>
                                @endcan
                                @can('assignments.delete')
                                    <button
                                        type="button"
                                        @click="$dispatch('open-delete-confirm', { id: '{{ $assignment->id }}', name: @js($assignment->title), type: 'assignments' })"
                                        class="text-error hover:underline"
                                    >
                                        Delete
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($assignments->hasPages())
            <div class="flex items-center justify-between">
                <p class="text-body-sm text-on-surface-variant">
                    Showing {{ $assignments->firstItem() }} to {{ $assignments->lastItem() }} of {{ $assignments->total() }} assignments
                </p>
                {{ $assignments->links() }}
            </div>
        @endif
    @endif
</div>
