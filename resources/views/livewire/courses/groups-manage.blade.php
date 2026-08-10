@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    <div>
        <a href="{{ route('assessments.index', $course) }}" class="text-body-sm text-primary hover:underline inline-flex items-center gap-space-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Assessments
        </a>
        <h1 class="font-headline-md text-headline-md text-on-surface mt-space-sm">Manage Groups</h1>
    </div>

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Create group -->
    <form wire:submit="createGroup" class="bg-surface border border-outline-variant rounded-lg p-space-lg flex items-end gap-space-md">
        <div class="flex-1">
            <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">New Group Name</label>
            <input type="text" wire:model="newGroupName" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
            @error('newGroupName') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity inline-flex items-center gap-space-sm">
            <span class="material-symbols-outlined">add</span>
            Create Group
        </button>
    </form>

    @if ($unassignedStudents->isNotEmpty())
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
            <p class="font-label-sm text-label-sm text-secondary mb-space-xs">Unassigned Students ({{ $unassignedStudents->count() }})</p>
            <p class="text-body-sm text-on-surface-variant">{{ $unassignedStudents->pluck('user.name')->implode(', ') }}</p>
        </div>
    @endif

    <!-- Groups -->
    <div class="space-y-space-md">
        @forelse ($groups as $group)
            <div wire:key="group-{{ $group->id }}" class="bg-surface border border-outline-variant rounded-lg p-space-lg space-y-space-md">
                <div class="flex items-center justify-between gap-space-md">
                    @if ($renamingGroupId === $group->id)
                        <form wire:submit="saveRename" class="flex items-center gap-space-sm flex-1">
                            <input type="text" wire:model="renameValue" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                            <button type="submit" class="text-primary text-body-sm font-medium hover:underline">Save</button>
                            <button type="button" wire:click="cancelRename" class="text-on-surface-variant text-body-sm hover:underline">Cancel</button>
                        </form>
                    @else
                        <p class="font-label-lg text-label-lg text-on-surface">{{ $group->name }}</p>
                        <div class="flex gap-space-sm">
                            <button type="button" wire:click="startRename('{{ $group->id }}')" class="p-2 hover:bg-surface-container rounded transition text-primary inline-flex">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <button type="button" wire:click="deleteGroup('{{ $group->id }}')" class="p-2 hover:bg-surface-container rounded transition text-error inline-flex">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-space-xs">
                    @forelse ($group->members as $member)
                        <span class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full bg-surface-container text-body-xs text-on-surface">
                            {{ $member->user->name }}
                            <button type="button" wire:click="removeStudent('{{ $member->id }}')" class="text-error">&times;</button>
                        </span>
                    @empty
                        <p class="text-body-sm text-on-surface-variant">No members yet.</p>
                    @endforelse
                </div>

                @if ($assigningGroupId === $group->id)
                    <div class="border border-outline-variant rounded-lg p-space-md space-y-space-xs max-h-48 overflow-y-auto">
                        @forelse ($allStudents as $coursePerson)
                            <button
                                type="button"
                                wire:click="addStudent('{{ $group->id }}', '{{ $coursePerson->user_id }}')"
                                class="w-full text-left px-space-sm py-space-xs rounded hover:bg-surface-container text-body-sm text-on-surface flex items-center justify-between"
                            >
                                {{ $coursePerson->user->name }}
                                @if (in_array($coursePerson->user_id, $assignedUserIds, true))
                                    <span class="text-body-xs text-on-surface-variant">move here</span>
                                @endif
                            </button>
                        @empty
                            <p class="text-body-sm text-on-surface-variant">No students enrolled.</p>
                        @endforelse
                        <button type="button" wire:click="cancelAssigning" class="text-body-sm text-on-surface-variant hover:underline mt-space-xs">Close</button>
                    </div>
                @else
                    <button type="button" wire:click="startAssigning('{{ $group->id }}')" class="text-primary text-body-sm font-medium hover:underline inline-flex items-center gap-space-xs">
                        <span class="material-symbols-outlined text-[16px]">person_add</span>
                        Add Student
                    </button>
                @endif
            </div>
        @empty
            <div class="bg-surface border border-outline-variant rounded-lg p-8 text-center text-body-sm text-on-surface-variant">
                No groups yet. Create one above.
            </div>
        @endforelse
    </div>
</div>
