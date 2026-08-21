@section('title', 'Redis Management')

<div class="space-y-space-lg" x-data="{ deleteKey: null, showModal: false, showFlushModal: false }">
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
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Redis Management</h1>
            <p class="text-body-sm text-on-surface-variant mt-1">Monitor and manage the Redis instance (local only)</p>
        </div>
        <button type="button" @click="showFlushModal = true" class="px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            Flush Database
        </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-space-md">
        <div class="bg-surface border border-outline-variant rounded-lg p-space-md">
            <p class="font-label-sm text-label-sm text-secondary uppercase">Version</p>
            <p class="font-body-md text-body-md text-on-surface mt-1">{{ $info['redis_version'] }}</p>
        </div>
        <div class="bg-surface border border-outline-variant rounded-lg p-space-md">
            <p class="font-label-sm text-label-sm text-secondary uppercase">Memory Used</p>
            <p class="font-body-md text-body-md text-on-surface mt-1">{{ $info['used_memory_human'] }}</p>
        </div>
        <div class="bg-surface border border-outline-variant rounded-lg p-space-md">
            <p class="font-label-sm text-label-sm text-secondary uppercase">Connected Clients</p>
            <p class="font-body-md text-body-md text-on-surface mt-1">{{ $info['connected_clients'] }}</p>
        </div>
        <div class="bg-surface border border-outline-variant rounded-lg p-space-md">
            <p class="font-label-sm text-label-sm text-secondary uppercase">Uptime (days)</p>
            <p class="font-body-md text-body-md text-on-surface mt-1">{{ $info['uptime_in_days'] }}</p>
        </div>
        <div class="bg-surface border border-outline-variant rounded-lg p-space-md">
            <p class="font-label-sm text-label-sm text-secondary uppercase">Keys</p>
            <p class="font-body-md text-body-md text-on-surface mt-1">{{ $info['total_keys'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-space-lg items-start">
        <div class="lg:col-span-2 bg-surface border border-outline-variant rounded-lg overflow-hidden">
            <div class="p-space-md border-b border-outline-variant">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search keys..."
                    class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                />
            </div>

            @if ($keys->isEmpty())
                <div class="p-8 text-center">
                    <p class="text-body-md text-on-surface-variant">No keys found.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-outline-variant bg-surface-container-lowest">
                                <th scope="col" class="px-space-lg py-space-md text-left font-label-md text-label-md text-secondary uppercase">Key</th>
                                <th scope="col" class="px-space-lg py-space-md text-right font-label-md text-label-md text-secondary uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($keys as $key)
                                <tr class="border-b border-outline-variant last:border-0 {{ $selectedKey === $key ? 'bg-surface-container-lowest' : '' }}">
                                    <td class="px-space-lg py-space-md">
                                        <button type="button" wire:click="selectKey('{{ $key }}')" class="font-body-sm text-body-sm text-on-surface hover:text-primary text-left break-all">
                                            {{ $key }}
                                        </button>
                                    </td>
                                    <td class="px-space-lg py-space-md text-right whitespace-nowrap">
                                        <button type="button" @click="deleteKey = '{{ $key }}'; showModal = true" class="font-label-md text-label-md text-error hover:underline">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-space-md border-t border-outline-variant">
                    {{ $keys->links() }}
                </div>
            @endif
        </div>

        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg">
            @if ($selectedKey)
                <div class="space-y-space-md">
                    <div>
                        <p class="font-label-sm text-label-sm text-secondary uppercase">Key</p>
                        <p class="font-body-sm text-body-sm text-on-surface mt-1 break-all">{{ $selectedKey }}</p>
                    </div>
                    <div class="flex gap-space-lg">
                        <div>
                            <p class="font-label-sm text-label-sm text-secondary uppercase">Type</p>
                            <p class="font-body-sm text-body-sm text-on-surface mt-1">{{ $selectedType }}</p>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm text-secondary uppercase">TTL</p>
                            <p class="font-body-sm text-body-sm text-on-surface mt-1">{{ $selectedTtl !== null ? $selectedTtl.'s' : 'No expiry' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="font-label-sm text-label-sm text-secondary uppercase mb-space-xs">Value</p>
                        <textarea
                            wire:model="editValue"
                            rows="12"
                            @disabled($selectedType !== 'string')
                            class="w-full px-space-md py-space-sm border border-outline rounded-lg font-mono text-body-sm focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:bg-surface-container-lowest disabled:text-on-surface-variant"
                        ></textarea>
                        @if ($selectedType !== 'string')
                            <p class="font-body-sm text-body-sm text-on-surface-variant mt-space-xs">Read-only preview for {{ $selectedType }} values.</p>
                        @endif
                    </div>
                    <div class="flex gap-space-md">
                        @if ($selectedType === 'string')
                            <button type="button" wire:click="save" class="flex-1 px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                                Save
                            </button>
                        @endif
                        <button type="button" wire:click="closeKey" class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                            Close
                        </button>
                    </div>
                </div>
            @else
                <p class="text-body-md text-on-surface-variant text-center">Select a key to view details.</p>
            @endif
        </div>
    </div>

    <!-- Delete Key Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50">
        <div @click="showModal = false" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">delete</span>
                        </div>
                    </div>
                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Delete Key</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant break-all" x-text="deleteKey"></p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">This action cannot be undone.</p>
                    </div>
                    <div class="flex gap-space-md pt-space-md">
                        <button @click="showModal = false" type="button" class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                            Cancel
                        </button>
                        <button @click="showModal = false; $wire.call('deleteKey', deleteKey)" type="button" class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flush Database Modal -->
    <div x-data="{ confirmText: '' }" x-show="showFlushModal" x-cloak class="fixed inset-0 z-50">
        <div @click="showFlushModal = false" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">warning</span>
                        </div>
                    </div>
                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Flush Database</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">This will permanently delete ALL keys in this Redis database. This action cannot be undone.</p>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">
                            Type <span class="font-medium">flush</span> to confirm
                        </label>
                        <input type="text" x-model="confirmText" autocomplete="off" class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                    <div class="flex gap-space-md pt-space-md">
                        <button @click="showFlushModal = false; confirmText = ''" type="button" class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                            Cancel
                        </button>
                        <button
                            :disabled="confirmText !== 'flush'"
                            :class="confirmText !== 'flush' ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'"
                            @click="showFlushModal = false; confirmText = ''; $wire.call('flushDatabase')"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md transition-opacity"
                        >
                            Flush
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
