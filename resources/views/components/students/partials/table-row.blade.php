@props(['student'])

<tr wire:key="student-{{ $student->id }}" class="hover:bg-surface-container-lowest transition-colors">
    @can('students.delete')
        <td class="px-space-lg py-space-md">
            <input type="checkbox" x-model="selected" value="{{ $student->id }}" data-student-checkbox aria-label="Select {{ $student->name }}">
        </td>
    @endcan
    <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $student->name }}</td>
    <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $student->email }}</td>
    <td class="px-space-lg py-space-md text-right whitespace-nowrap space-x-space-md">
        @can('students.edit')
            <a href="{{ route('students.edit', $student) }}" class="font-label-md text-label-md text-primary hover:underline">Edit</a>
        @endcan
        @can('students.delete')
            <button type="button" @click="deleteId = '{{ $student->id }}'; showDeleteModal = true" class="font-label-md text-label-md text-error hover:underline">Delete</button>
        @endcan
    </td>
</tr>
