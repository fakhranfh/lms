@props(['student'])

<tr wire:key="student-{{ $student->id }}" class="hover:bg-surface-container-lowest transition-colors">
    @can('students.delete')
        <td class="px-space-lg py-space-md">
            <input type="checkbox" :checked="selectAllMatching || selected.includes('{{ $student->id }}')" @change="toggleStudent('{{ $student->id }}', $event.target.checked)" aria-label="Select {{ $student->name }}">
        </td>
    @endcan
    <td class="px-space-lg py-space-md text-body-md text-on-surface">
        <div class="flex items-center gap-space-sm">
            <x-avatar :user="$student" size="8" />
            <span>{{ $student->name }}</span>
        </div>
    </td>
    <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $student->email }}</td>
    <td class="px-space-lg py-space-md text-right whitespace-nowrap space-x-space-xs">
        @can('students.edit')
            <button type="button" wire:click="regenerateLoginLink('{{ $student->id }}')" wire:loading.attr="disabled" wire:target="regenerateLoginLink('{{ $student->id }}')"
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors disabled:opacity-50"
                title="Generate new login link" aria-label="Generate new login link for {{ $student->name }}">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
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
