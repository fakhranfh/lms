@section('title', $course->title)

<div class="space-y-space-lg">
    @include('livewire.courses.partials.course-header', ['course' => $course, 'courseTabs' => $courseTabs, 'teacher' => null])

    @if ($errorMessage)
        <div class="px-gutter py-space-md bg-error/10 border border-error/20 rounded-lg flex items-center gap-space-md">
            <span class="material-symbols-outlined text-error text-[20px]" data-weight="fill">error</span>
            <p class="font-body-md text-body-md text-error">{{ $errorMessage }}</p>
        </div>
    @endif

    <div class="space-y-space-lg">
        <div class="flex w-full sm:w-1/4 bg-surface border border-outline-variant rounded-lg overflow-hidden divide-x divide-outline-variant">
            <button
                type="button"
                wire:click="selectSubTab('students')"
                wire:loading.attr="disabled"
                wire:target="selectSubTab('students'), selectSubTab('groups'), selectSubTab('teachers')"
                class="flex-1 py-space-md text-center font-label-md text-label-md transition disabled:opacity-60 disabled:cursor-not-allowed {{ $activeSubTab === 'students' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}"
            >
                <span class="block font-headline-sm text-headline-sm">{{ $studentsCount }}</span>
                Students
            </button>
            <button
                type="button"
                wire:click="selectSubTab('groups')"
                wire:loading.attr="disabled"
                wire:target="selectSubTab('students'), selectSubTab('groups'), selectSubTab('teachers')"
                class="flex-1 py-space-md text-center font-label-md text-label-md transition disabled:opacity-60 disabled:cursor-not-allowed {{ $activeSubTab === 'groups' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}"
            >
                <span class="block font-headline-sm text-headline-sm">{{ $groupsCount }}</span>
                {{ str()->plural('Group', $groupsCount) }}
            </button>
            <button
                type="button"
                wire:click="selectSubTab('teachers')"
                wire:loading.attr="disabled"
                wire:target="selectSubTab('students'), selectSubTab('groups'), selectSubTab('teachers')"
                class="flex-1 py-space-md text-center font-label-md text-label-md transition disabled:opacity-60 disabled:cursor-not-allowed {{ $activeSubTab === 'teachers' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}"
            >
                <span class="block font-headline-sm text-headline-sm">{{ $teachersCount }}</span>
                Teachers
            </button>
        </div>

        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div wire:loading.grid wire:target="selectSubTab('students'), selectSubTab('teachers')" class="hidden grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-px bg-outline-variant animate-pulse">
                @for ($i = 0; $i < 9; $i++)
                    <div class="bg-surface p-space-lg flex flex-col items-center gap-space-sm">
                        <div class="w-12 h-12 rounded-full bg-surface-container"></div>
                        <div class="h-4 bg-surface-container rounded w-2/3"></div>
                    </div>
                @endfor
            </div>

            <div wire:loading.block wire:target="selectSubTab('groups')" class="hidden w-full p-space-lg space-y-space-md animate-pulse">
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

            <div wire:loading.remove wire:target="selectSubTab('students'), selectSubTab('teachers'), selectSubTab('groups')">
                @if ($activeSubTab === 'students')
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-px bg-outline-variant">
                        @forelse ($students as $coursePerson)
                            <div wire:key="student-{{ $coursePerson->id }}" class="bg-surface p-space-lg flex flex-col items-center text-center gap-space-sm">
                                <x-avatar :user="$coursePerson->user" size="12" />
                                <p class="font-label-lg text-label-lg text-on-surface uppercase">{{ $coursePerson->user->name }}</p>
                            </div>
                        @empty
                            <div class="bg-surface p-space-lg text-center text-body-sm text-on-surface-variant col-span-full">No students enrolled yet.</div>
                        @endforelse
                        @for ($i = 0; $i < (3 - $students->count() % 3) % 3; $i++)
                            <div class="bg-surface hidden md:block"></div>
                        @endfor
                    </div>
                @endif

                @if ($activeSubTab === 'teachers')
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-px bg-outline-variant">
                        @forelse ($teachers as $coursePerson)
                            <div wire:key="teacher-{{ $coursePerson->id }}" class="bg-surface p-space-lg flex flex-col items-center text-center gap-space-sm">
                                <div class="rounded-full ring-2 ring-secondary ring-offset-2">
                                    <x-avatar :user="$coursePerson->user" size="12" />
                                </div>
                                <p class="font-label-lg text-label-lg text-on-surface">{{ $coursePerson->user->name }}</p>
                                <span class="inline-flex items-center px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-primary/10 text-primary">
                                    {{ str($coursePerson->role_in_course->value)->title() }}
                                </span>
                            </div>
                        @empty
                            <div class="bg-surface p-space-lg text-center text-body-sm text-on-surface-variant col-span-full">No teachers assigned yet.</div>
                        @endforelse
                        @for ($i = 0; $i < (3 - $teachers->count() % 3) % 3; $i++)
                            <div class="bg-surface hidden md:block"></div>
                        @endfor
                    </div>
                @endif

                @if ($activeSubTab === 'groups')
                    <div class="p-space-lg space-y-space-md">
                        <div>
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">Class Group</h2>
                        </div>

                        @if ($isStudent)
                            @if ($ownGroup)
                                <div class="border border-outline-variant rounded-lg p-space-lg">
                                    <p class="font-label-lg text-label-lg text-on-surface mb-space-md">{{ $ownGroup->name }}</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md">
                                        @forelse ($ownGroup->members as $member)
                                            <div class="flex items-center gap-space-sm">
                                                <x-avatar :user="$member->user" size="8" />
                                                <p class="font-label-md text-label-md text-on-surface">{{ $member->user->name }}</p>
                                            </div>
                                        @empty
                                            <p class="text-body-sm text-on-surface-variant">No members yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @else
                                <p class="text-body-sm text-on-surface-variant">You are not in a group yet.</p>
                            @endif
                        @else
                            @if ($unassignedStudents->isNotEmpty())
                                <div class="bg-surface-container/50 border border-outline-variant rounded-lg p-space-md">
                                    <p class="font-label-sm text-label-sm text-secondary mb-space-xs">Unassigned Students ({{ $unassignedStudents->count() }})</p>
                                    <p class="text-body-sm text-on-surface-variant">{{ $unassignedStudents->pluck('user.name')->implode(', ') }}</p>
                                </div>
                            @endif

                            @if ($canManageGroups)
                                <form wire:submit="createGroup" class="flex items-end gap-space-md">
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
                            @endif

                            <div class="space-y-space-sm">
                                @forelse ($groups as $group)
                                    <div wire:key="group-{{ $group->id }}" x-data="{ open: true }" class="border border-outline-variant rounded-lg overflow-hidden">
                                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-space-lg py-space-md bg-surface-container/30">
                                            @if ($canManageGroups && $renamingGroupId === $group->id)
                                                <form wire:submit="saveRename" @click.stop class="flex items-center gap-space-sm flex-1">
                                                    <input type="text" wire:model="renameValue" class="flex-1 px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                                                    <button type="submit" class="text-primary text-body-sm font-medium hover:underline">Save</button>
                                                    <button type="button" wire:click="cancelRename" class="text-on-surface-variant text-body-sm hover:underline">Cancel</button>
                                                </form>
                                            @else
                                                <span class="font-label-lg text-label-lg text-on-surface">{{ $group->name }}</span>
                                                <div class="flex items-center gap-space-sm">
                                                    @if ($canManageGroups)
                                                        <span @click.stop wire:click="startRename('{{ $group->id }}')" class="p-1 hover:bg-surface-container rounded transition text-primary inline-flex">
                                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                                        </span>
                                                        <span @click.stop wire:click="deleteGroup('{{ $group->id }}')" class="p-1 hover:bg-surface-container rounded transition text-error inline-flex">
                                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                                        </span>
                                                    @endif
                                                    <span class="material-symbols-outlined text-on-surface-variant transition" :class="open ? 'rotate-180' : ''">expand_more</span>
                                                </div>
                                            @endif
                                        </button>

                                        <div x-show="open" x-transition class="p-space-lg space-y-space-md">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md">
                                                @forelse ($group->members as $member)
                                                    <div class="flex items-center justify-between gap-space-sm">
                                                        <div class="flex items-center gap-space-sm">
                                                            <x-avatar :user="$member->user" size="8" />
                                                            <p class="font-label-md text-label-md text-on-surface">{{ $member->user->name }}</p>
                                                        </div>
                                                        @if ($canManageGroups)
                                                            <button type="button" wire:click="removeStudent('{{ $member->id }}')" class="text-error text-[18px] material-symbols-outlined">close</button>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <p class="text-body-sm text-on-surface-variant">No members yet.</p>
                                                @endforelse
                                            </div>

                                            @if ($canManageGroups)
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
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="border border-outline-variant rounded-lg p-8 text-center text-body-sm text-on-surface-variant">
                                        No groups yet.
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
