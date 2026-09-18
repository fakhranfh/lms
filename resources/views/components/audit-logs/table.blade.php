@props([
    'auditLogs',
    'perPage' => 15,
    'sort' => 'created_at',
    'direction' => 'desc',
])

<x-ui.livewire-data-table
    :columns="[
        ['key' => 'created_at', 'label' => 'Timestamp'],
        ['key' => 'user', 'label' => 'User', 'sortable' => false],
        ['key' => 'event', 'label' => 'Event', 'sortable' => false],
        ['key' => 'model', 'label' => 'Model', 'sortable' => false],
        ['key' => 'description', 'label' => 'Description', 'sortable' => false],
    ]"
    :items="$auditLogs"
    :sort="$sort"
    :direction="$direction"
    :perPage="$perPage"
    loadingTarget="dateFrom,dateTo,userId,modelType,event,search,perPage"
>
    @forelse ($auditLogs as $log)
        <tr wire:key="audit-log-{{ $log->id }}" class="hover:bg-surface-container-lowest transition-colors">
            <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">{{ $log->created_at_display?->format('M j, Y g:i A') }}</td>
            <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $log->user?->email ?? 'system' }}</td>
            <td class="px-space-lg py-space-md">
                <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-sm text-label-sm bg-primary-container text-on-primary-container capitalize">
                    {{ $log->event }}
                </span>
            </td>
            <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ class_basename($log->auditable_type) }}</td>
            <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $log->description }}</td>
            <td class="px-space-lg py-space-md text-right">
                <button type="button" wire:click="show('{{ $log->id }}')" class="px-space-md py-space-xs rounded-lg bg-outline-variant text-on-surface font-label-sm text-label-sm hover:bg-outline transition-colors">
                    View
                </button>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                No audit logs found.
            </td>
        </tr>
    @endforelse
</x-ui.livewire-data-table>
