@section('title', 'Audit Logs')

<div class="space-y-space-lg">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Audit Logs</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Immutable trail of every data mutation across schools</p>
        </div>
        <button
            type="button"
            wire:click="exportCsv"
            class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
        >
            Export CSV
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-space-md">
        <input type="date" wire:model.live="dateFrom" placeholder="From" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <input type="date" wire:model.live="dateTo" placeholder="To" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <select wire:model.live="event" class="px-space-md py-space-sm border border-outline rounded-lg">
            <option value="">All events</option>
            <option value="created">Created</option>
            <option value="updated">Updated</option>
            <option value="deleted">Deleted</option>
            <option value="restored">Restored</option>
        </select>
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search description, user, ID" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <input type="text" wire:model.live.debounce.400ms="modelType" placeholder="Model type (e.g. App\Models\Course)" class="px-space-md py-space-sm border border-outline rounded-lg" />
    </div>

    <div class="overflow-x-auto border border-outline rounded-lg">
        <table class="w-full text-left">
            <thead class="bg-surface-container text-label-sm text-on-surface-variant">
                <tr>
                    <th class="px-space-md py-space-sm">Timestamp</th>
                    <th class="px-space-md py-space-sm">User</th>
                    <th class="px-space-md py-space-sm">Event</th>
                    <th class="px-space-md py-space-sm">Model</th>
                    <th class="px-space-md py-space-sm">Description</th>
                    <th class="px-space-md py-space-sm">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($auditLogs as $log)
                    <tr class="border-t border-outline/30">
                        <td class="px-space-md py-space-sm text-body-sm">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $log->user?->email ?? 'system' }}</td>
                        <td class="px-space-md py-space-sm text-body-sm capitalize">{{ $log->event }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ class_basename($log->auditable_type) }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">{{ $log->description }}</td>
                        <td class="px-space-md py-space-sm text-body-sm">
                            <button type="button" wire:click="show('{{ $log->id }}')" class="text-primary hover:underline">View</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-space-md py-space-lg text-center text-body-sm text-on-surface-variant">No audit logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $auditLogs->links() }}

    @if ($selectedAuditLog)
        <div class="fixed inset-0 z-50">
            <div wire:click="closeModal" class="fixed inset-0 bg-black bg-opacity-50"></div>

            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-2xl w-full max-h-[85vh] overflow-y-auto">
                    <div class="p-space-lg space-y-space-lg">
                        <div class="flex items-center justify-between">
                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Audit Log Detail</h3>
                            <button type="button" wire:click="closeModal" class="text-on-surface-variant hover:text-on-surface">&times;</button>
                        </div>

                        <div class="grid grid-cols-2 gap-space-sm text-body-sm">
                            <div><span class="text-on-surface-variant">User:</span> {{ $selectedAuditLog->user?->email ?? 'system' }}</div>
                            <div><span class="text-on-surface-variant">Event:</span> {{ $selectedAuditLog->event }}</div>
                            <div><span class="text-on-surface-variant">Model:</span> {{ class_basename($selectedAuditLog->auditable_type) }} ({{ $selectedAuditLog->auditable_id }})</div>
                            <div><span class="text-on-surface-variant">Timestamp:</span> {{ $selectedAuditLog->created_at?->toDateTimeString() }}</div>
                            <div><span class="text-on-surface-variant">IP:</span> {{ $selectedAuditLog->ip_address ?? '—' }}</div>
                            <div class="truncate"><span class="text-on-surface-variant">User agent:</span> {{ $selectedAuditLog->user_agent ?? '—' }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-space-md">
                            <div>
                                <h4 class="font-label-md text-label-md text-on-surface-variant mb-space-sm">Old Values</h4>
                                <pre class="text-body-sm bg-surface-container rounded-lg p-space-md overflow-x-auto">{{ json_encode($selectedAuditLog->old_values, JSON_PRETTY_PRINT) ?: 'null' }}</pre>
                            </div>
                            <div>
                                <h4 class="font-label-md text-label-md text-on-surface-variant mb-space-sm">New Values</h4>
                                <pre class="text-body-sm bg-surface-container rounded-lg p-space-md overflow-x-auto">{{ json_encode($selectedAuditLog->new_values, JSON_PRETTY_PRINT) ?: 'null' }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
