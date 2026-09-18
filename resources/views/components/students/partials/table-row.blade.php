@props(['student'])

<tr wire:key="student-{{ $student->id }}" x-data="{ get isDeleting() { return deletingIds.includes('{{ $student->id }}') } }" class="hover:bg-surface-container-lowest transition-colors">
    @can('students.delete')
        <td class="px-space-lg py-space-md">
            <input type="checkbox" :disabled="isDeleting" :checked="selectAllMatching || selected.includes('{{ $student->id }}')" @change="toggleStudent('{{ $student->id }}', $event.target.checked)" aria-label="Select {{ $student->name }}">
        </td>
    @endcan

    <template x-if="isDeleting">
        <td class="px-space-lg py-space-md">
            <div class="flex items-center gap-space-sm">
                <x-ui.skeleton-box class="h-8 w-8 rounded-full flex-shrink-0" />
                <x-ui.skeleton-box class="h-4 w-32" />
            </div>
        </td>
    </template>
    <td x-show="!isDeleting" class="px-space-lg py-space-md text-body-md text-on-surface">
        <div class="flex items-center gap-space-sm">
            <x-avatar :user="$student" size="8" />
            <span>{{ $student->name }}</span>
        </div>
    </td>

    <template x-if="isDeleting">
        <td class="px-space-lg py-space-md">
            <x-ui.skeleton-box class="h-4 w-40" />
        </td>
    </template>
    <td x-show="!isDeleting" class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $student->email }}</td>

    <template x-if="isDeleting">
        <td class="px-space-lg py-space-md text-right">
            <x-ui.skeleton-box class="h-4 w-16 ml-auto" />
        </td>
    </template>
    <td x-show="!isDeleting" class="px-space-lg py-space-md text-right whitespace-nowrap space-x-space-md">
        @can('students.edit')
            <button type="button" wire:click="regenerateLoginLink('{{ $student->id }}')" wire:loading.attr="disabled" wire:target="regenerateLoginLink('{{ $student->id }}')"
                class="font-label-md text-label-md text-primary hover:underline disabled:opacity-50">
                Generate Link
            </button>
            <a href="{{ route('students.edit', $student) }}"
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-primary hover:bg-surface-container transition-colors"
                title="Edit" aria-label="Edit {{ $student->name }}">
                <span class="material-symbols-outlined text-[18px]">edit</span>
            </a>
        @endcan
        @can('students.delete')
            <button type="button" @click="deleteId = '{{ $student->id }}'; showDeleteModal = true"
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-error hover:bg-error/10 transition-colors"
                title="Delete" aria-label="Delete {{ $student->name }}">
                <span class="material-symbols-outlined text-[18px]">delete</span>
            </button>
        @endcan
    </td>
</tr>
