@section('title', $course->title)

<div
    class="space-y-space-lg"
    x-data="{ deleteId: null, deleteName: null, deleteType: null, showDeleteModal: false, switchingTab: null, enrollingId: null, selectedIds: [], deletingIds: [] }"
>
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
                @click="selectedIds = []; switchingTab = 'students'; $wire.selectSubTab('students').then(() => switchingTab = null)"
                :disabled="switchingTab !== null"
                class="flex-1 py-space-md text-center font-label-md text-label-md transition disabled:opacity-60 disabled:cursor-not-allowed {{ $activeSubTab === 'students' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}"
            >
                <span class="block font-headline-sm text-headline-sm">{{ $studentsCount }}</span>
                Students
            </button>
            <button
                type="button"
                @click="selectedIds = []; switchingTab = 'groups'; $wire.selectSubTab('groups').then(() => switchingTab = null)"
                :disabled="switchingTab !== null"
                class="flex-1 py-space-md text-center font-label-md text-label-md transition disabled:opacity-60 disabled:cursor-not-allowed {{ $activeSubTab === 'groups' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}"
            >
                <span class="block font-headline-sm text-headline-sm">{{ $groupsCount }}</span>
                {{ str()->plural('Group', $groupsCount) }}
            </button>
            <button
                type="button"
                @click="selectedIds = []; switchingTab = 'teachers'; $wire.selectSubTab('teachers').then(() => switchingTab = null)"
                :disabled="switchingTab !== null"
                class="flex-1 py-space-md text-center font-label-md text-label-md transition disabled:opacity-60 disabled:cursor-not-allowed {{ $activeSubTab === 'teachers' ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container' }}"
            >
                <span class="block font-headline-sm text-headline-sm">{{ $teachersCount }}</span>
                Teachers
            </button>
        </div>

        <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div x-show="switchingTab === 'students' || switchingTab === 'teachers'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-px bg-outline-variant animate-pulse">
                @for ($i = 0; $i < 9; $i++)
                    <div class="bg-surface p-space-lg flex flex-col items-center gap-space-sm">
                        <div class="w-12 h-12 rounded-full bg-surface-container"></div>
                        <div class="h-4 bg-surface-container rounded w-2/3"></div>
                    </div>
                @endfor
            </div>

            <div x-show="switchingTab === 'groups'" x-cloak class="w-full p-space-lg space-y-space-md animate-pulse">
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

            <div x-show="switchingTab === null">
                @if ($activeSubTab === 'students')
                    @if ($canManageGroups)
                        <div class="p-space-lg border-b border-outline-variant space-y-space-sm" x-data="{ open: false }" @click.outside="open = false">
                            <label class="block font-label-sm text-label-sm text-secondary">Enroll Student</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="studentSearch"
                                    @click="open = true"
                                    @focus="open = true"
                                    placeholder="Search by name or email"
                                    class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                                />
                                <div x-show="open" x-cloak class="mt-space-xs border border-outline-variant rounded-lg max-h-48 overflow-y-auto divide-y divide-outline-variant">
                                    <div wire:loading wire:target="studentSearch" class="p-space-md space-y-space-sm animate-pulse">
                                        @for ($i = 0; $i < 3; $i++)
                                            <div class="h-4 bg-surface-container rounded w-3/4"></div>
                                        @endfor
                                    </div>
                                    <div wire:loading.remove wire:target="studentSearch">
                                        @forelse ($this->studentSearchResults as $user)
                                            <button
                                                type="button"
                                                wire:key="student-result-{{ $user->id }}"
                                                @click="open = false; enrollingId = '{{ $user->id }}'; $wire.enrollStudent('{{ $user->id }}').finally(() => enrollingId = null)"
                                                class="w-full text-left px-space-md py-space-sm hover:bg-surface-container text-body-sm text-on-surface flex items-center justify-between"
                                            >
                                                <span>{{ $user->name }} <span class="text-on-surface-variant">({{ $user->email }})</span></span>
                                                <span class="material-symbols-outlined text-[18px] text-primary">person_add</span>
                                            </button>
                                        @empty
                                            <p class="px-space-md py-space-sm text-body-sm text-on-surface-variant">No matching students found.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($canGenerateStudents)
                        <div class="p-space-lg border-b border-outline-variant space-y-space-sm bg-secondary/5" x-data="{ generating: false }">
                            <label class="block font-label-sm text-label-sm text-secondary">Generate Dummy Students (Dev Only)</label>
                            <form
                                @submit.prevent="generating = true; $wire.generateStudents().finally(() => generating = false)"
                                class="flex items-end gap-space-md"
                            >
                                <div class="flex-1 max-w-[160px]">
                                    <input
                                        type="number"
                                        min="1"
                                        max="100"
                                        wire:model="generateStudentCount"
                                        class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                                    />
                                    @error('generateStudentCount') <p class="text-body-xs text-error mt-space-xs">{{ $message }}</p> @enderror
                                </div>
                                <button
                                    type="submit"
                                    :disabled="generating"
                                    class="px-space-lg py-space-sm bg-secondary text-on-secondary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity disabled:opacity-60 disabled:cursor-not-allowed inline-flex items-center gap-space-sm"
                                >
                                    <span class="material-symbols-outlined" x-show="!generating">bolt</span>
                                    <svg x-show="generating" x-cloak class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    <span x-text="generating ? 'Generating...' : 'Generate & Enroll'"></span>
                                </button>
                            </form>
                        </div>
                    @endif

                    @if ($canManageGroups && $students->isNotEmpty())
                        <div wire:key="student-toolbar-{{ $studentsCount }}" class="p-space-md border-b border-outline-variant flex items-center justify-between bg-surface-container/30" x-data="{ allIds: @js($students->pluck('id')) }">
                            <label class="flex items-center gap-space-sm font-label-sm text-label-sm text-on-surface-variant cursor-pointer">
                                <input
                                    type="checkbox"
                                    :checked="allIds.length > 0 && selectedIds.length === allIds.length"
                                    @change="selectedIds = $event.target.checked ? [...allIds] : []"
                                    class="w-4 h-4 rounded border-outline text-primary focus:ring-primary/50"
                                />
                                <span x-text="selectedIds.length > 0 ? selectedIds.length + ' selected' : 'Select all'"></span>
                            </label>
                            <button
                                type="button"
                                :disabled="selectedIds.length === 0"
                                @click="deleteType = 'bulk-students'; deleteName = selectedIds.length + ' selected student' + (selectedIds.length === 1 ? '' : 's'); showDeleteModal = true"
                                class="px-space-md py-1 text-body-sm text-error hover:bg-error/10 rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center gap-space-xs"
                            >
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                Delete Selected
                            </button>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-px bg-outline-variant">
                        <div x-show="enrollingId !== null" x-cloak class="bg-surface p-space-lg flex flex-col items-center gap-space-sm animate-pulse">
                            <div class="w-12 h-12 rounded-full bg-surface-container"></div>
                            <div class="h-4 bg-surface-container rounded w-2/3"></div>
                        </div>
                        <div x-show="enrollingId !== null" x-cloak class="bg-surface hidden sm:block"></div>
                        <div x-show="enrollingId !== null" x-cloak class="bg-surface hidden md:block"></div>
                        @forelse ($students as $coursePerson)
                            <div wire:key="student-{{ $coursePerson->id }}" class="bg-surface p-space-lg">
                                <div x-show="deletingIds.includes('{{ $coursePerson->id }}')" x-cloak class="flex flex-col items-center gap-space-sm animate-pulse">
                                    <div class="w-12 h-12 rounded-full bg-surface-container"></div>
                                    <div class="h-4 bg-surface-container rounded w-2/3"></div>
                                </div>
                                <div x-show="!deletingIds.includes('{{ $coursePerson->id }}')" class="relative flex flex-col items-center text-center gap-space-sm">
                                    @if ($canManageGroups)
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.includes('{{ $coursePerson->id }}')"
                                            @change="$event.target.checked ? selectedIds.push('{{ $coursePerson->id }}') : selectedIds = selectedIds.filter(id => id !== '{{ $coursePerson->id }}')"
                                            class="absolute top-0 left-0 w-4 h-4 rounded border-outline text-primary focus:ring-primary/50"
                                        />
                                        <button
                                            type="button"
                                            @click="deleteId = '{{ $coursePerson->id }}'; deleteName = @js($coursePerson->user->name); deleteType = 'student'; showDeleteModal = true"
                                            class="absolute top-0 right-0 p-1 text-on-surface-variant hover:text-error transition"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">close</span>
                                        </button>
                                    @endif
                                    <x-avatar :user="$coursePerson->user" size="12" />
                                    <p class="font-label-lg text-label-lg text-on-surface uppercase">{{ $coursePerson->user->name }}</p>
                                </div>
                            </div>
                        @empty
                            <div x-show="enrollingId === null" class="bg-surface p-space-lg text-center text-body-sm text-on-surface-variant col-span-full">No students enrolled yet.</div>
                        @endforelse
                        @for ($i = 0; $i < (3 - $students->count() % 3) % 3; $i++)
                            <div class="bg-surface hidden md:block"></div>
                        @endfor
                    </div>
                @endif

                @if ($activeSubTab === 'teachers')
                    @if ($canManageGroups)
                        <div class="p-space-lg border-b border-outline-variant space-y-space-sm" x-data="{ open: false }" @click.outside="open = false">
                            <label class="block font-label-sm text-label-sm text-secondary">Enroll Teacher</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="teacherSearch"
                                    @click="open = true"
                                    @focus="open = true"
                                    placeholder="Search by name or email"
                                    class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                                />
                                <div x-show="open" x-cloak class="mt-space-xs border border-outline-variant rounded-lg max-h-48 overflow-y-auto divide-y divide-outline-variant">
                                    <div wire:loading wire:target="teacherSearch" class="p-space-md space-y-space-sm animate-pulse">
                                        @for ($i = 0; $i < 3; $i++)
                                            <div class="h-4 bg-surface-container rounded w-3/4"></div>
                                        @endfor
                                    </div>
                                    <div wire:loading.remove wire:target="teacherSearch">
                                        @forelse ($this->teacherSearchResults as $user)
                                            <button
                                                type="button"
                                                wire:key="teacher-result-{{ $user->id }}"
                                                @click="open = false; enrollingId = '{{ $user->id }}'; $wire.enrollTeacher('{{ $user->id }}').finally(() => enrollingId = null)"
                                                class="w-full text-left px-space-md py-space-sm hover:bg-surface-container text-body-sm text-on-surface flex items-center justify-between"
                                            >
                                                <span>{{ $user->name }} <span class="text-on-surface-variant">({{ $user->email }})</span></span>
                                                <span class="material-symbols-outlined text-[18px] text-primary">person_add</span>
                                            </button>
                                        @empty
                                            <p class="px-space-md py-space-sm text-body-sm text-on-surface-variant">No matching teachers found.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($canManageGroups && $teachers->isNotEmpty())
                        <div wire:key="teacher-toolbar-{{ $teachersCount }}" class="p-space-md border-b border-outline-variant flex items-center justify-between bg-surface-container/30" x-data="{ allIds: @js($teachers->pluck('id')) }">
                            <label class="flex items-center gap-space-sm font-label-sm text-label-sm text-on-surface-variant cursor-pointer">
                                <input
                                    type="checkbox"
                                    :checked="allIds.length > 0 && selectedIds.length === allIds.length"
                                    @change="selectedIds = $event.target.checked ? [...allIds] : []"
                                    class="w-4 h-4 rounded border-outline text-primary focus:ring-primary/50"
                                />
                                <span x-text="selectedIds.length > 0 ? selectedIds.length + ' selected' : 'Select all'"></span>
                            </label>
                            <button
                                type="button"
                                :disabled="selectedIds.length === 0"
                                @click="deleteType = 'bulk-teachers'; deleteName = selectedIds.length + ' selected teacher' + (selectedIds.length === 1 ? '' : 's'); showDeleteModal = true"
                                class="px-space-md py-1 text-body-sm text-error hover:bg-error/10 rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center gap-space-xs"
                            >
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                Delete Selected
                            </button>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-px bg-outline-variant">
                        <div x-show="enrollingId !== null" x-cloak class="bg-surface p-space-lg flex flex-col items-center gap-space-sm animate-pulse">
                            <div class="w-12 h-12 rounded-full bg-surface-container"></div>
                            <div class="h-4 bg-surface-container rounded w-2/3"></div>
                        </div>
                        <div x-show="enrollingId !== null" x-cloak class="bg-surface hidden sm:block"></div>
                        <div x-show="enrollingId !== null" x-cloak class="bg-surface hidden md:block"></div>
                        @forelse ($teachers as $coursePerson)
                            <div wire:key="teacher-{{ $coursePerson->id }}" class="bg-surface p-space-lg">
                                <div x-show="deletingIds.includes('{{ $coursePerson->id }}')" x-cloak class="flex flex-col items-center gap-space-sm animate-pulse">
                                    <div class="w-12 h-12 rounded-full bg-surface-container"></div>
                                    <div class="h-4 bg-surface-container rounded w-2/3"></div>
                                </div>
                                <div x-show="!deletingIds.includes('{{ $coursePerson->id }}')" class="relative flex flex-col items-center text-center gap-space-sm">
                                    @if ($canManageGroups)
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.includes('{{ $coursePerson->id }}')"
                                            @change="$event.target.checked ? selectedIds.push('{{ $coursePerson->id }}') : selectedIds = selectedIds.filter(id => id !== '{{ $coursePerson->id }}')"
                                            class="absolute top-0 left-0 w-4 h-4 rounded border-outline text-primary focus:ring-primary/50"
                                        />
                                        <button
                                            type="button"
                                            @click="deleteId = '{{ $coursePerson->id }}'; deleteName = @js($coursePerson->user->name); deleteType = 'teacher'; showDeleteModal = true"
                                            class="absolute top-0 right-0 p-1 text-on-surface-variant hover:text-error transition"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">close</span>
                                        </button>
                                    @endif
                                    <div class="rounded-full ring-2 ring-secondary ring-offset-2">
                                        <x-avatar :user="$coursePerson->user" size="12" />
                                    </div>
                                    <p class="font-label-lg text-label-lg text-on-surface">{{ $coursePerson->user->name }}</p>
                                    <span class="inline-flex items-center px-space-sm py-1 rounded-full font-label-sm text-label-sm bg-primary/10 text-primary">
                                        {{ str($coursePerson->role_in_course->value)->title() }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div x-show="enrollingId === null" class="bg-surface p-space-lg text-center text-body-sm text-on-surface-variant col-span-full">No teachers assigned yet.</div>
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

    <!-- Unenroll Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50">
        <div
            @click="showDeleteModal = false"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        ></div>

        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Remove from Course</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            Are you sure you want to remove "<span class="font-medium" x-text="deleteName ?? 'this person'"></span>" from this course?
                            This action cannot be undone.
                        </p>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            @click="showDeleteModal = false"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="
                                showDeleteModal = false;
                                if (deleteType === 'teacher') {
                                    deletingIds = [deleteId];
                                    $wire.call('unenrollTeacher', deleteId).finally(() => deletingIds = []);
                                } else if (deleteType === 'student') {
                                    deletingIds = [deleteId];
                                    $wire.call('unenrollStudent', deleteId).finally(() => deletingIds = []);
                                } else if (deleteType === 'bulk-teachers') {
                                    deletingIds = [...selectedIds];
                                    $wire.call('bulkUnenrollTeachers', selectedIds).finally(() => { deletingIds = []; selectedIds = []; });
                                } else if (deleteType === 'bulk-students') {
                                    deletingIds = [...selectedIds];
                                    $wire.call('bulkUnenrollStudents', selectedIds).finally(() => { deletingIds = []; selectedIds = []; });
                                }
                            "
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
