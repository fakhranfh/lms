@extends('master')

@section('body_class', 'bg-background text-on-background min-h-screen flex flex-col font-body-md')

@section('content')
    <div class="flex h-screen flex-col">
        @if(!isset($skipTopbar) || !$skipTopbar)
            <x-topbar :title="$topbarTitle ?? 'Dashboard'" :showBackButton="$showBackButton ?? false" />
        @endif

        <div class="flex flex-1 overflow-hidden">
            @if(!isset($skipSidebar) || !$skipSidebar)
                <x-sidebar />
            @endif

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto py-space-lg px-gutter">
                <div class="max-w-7xl mx-auto">
                    @yield('app-content')
                </div>
            </main>
        </div>
    </div>

    <div
        x-data="{ open: false, itemId: null, itemName: null, itemType: null, confirmText: '' }"
        x-on:open-delete-confirm.window="open = true; itemId = $event.detail.id; itemName = $event.detail.name ?? null; itemType = $event.detail.type ?? null; confirmText = ''"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
        @click.self="open = false"
    >
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Delete confirmation</h2>
            <p class="font-body-md text-body-md text-secondary">
                Are you sure you want to delete
                <template x-if="itemName"><span>"<span class="font-medium" x-text="itemName"></span>"</span></template>
                <template x-if="!itemName"><span>this item</span></template>?
                This action cannot be undone.
            </p>
            <p x-show="itemType === 'courses'" class="font-body-sm text-body-sm text-error">
                This will also delete all of its sessions.
            </p>
            <div x-show="itemName">
                <label class="block font-label-sm text-label-sm text-secondary mb-space-xs">
                    Type <span class="font-medium" x-text="itemName"></span> to confirm
                </label>
                <input
                    type="text"
                    x-model="confirmText"
                    autocomplete="off"
                    class="w-full px-space-md py-space-sm border border-outline rounded-lg font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary/50"
                />
            </div>
            <div class="flex items-center justify-end gap-space-md">
                <button type="button" @click="open = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                <button
                    type="button"
                    :disabled="itemName && confirmText !== itemName"
                    :class="itemName && confirmText !== itemName ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'"
                    @click="Livewire.dispatch('delete-confirmed', { id: itemId }); open = false"
                    class="px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md transition-opacity"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const spacer = document.getElementById('sidebar-spacer');

            if (sidebar && spacer) {
                const isCollapsed = sidebar.style.width === '0px';

                if (isCollapsed) {
                    sidebar.style.width = '256px';
                    spacer.style.width = '256px';
                } else {
                    sidebar.style.width = '0px';
                    spacer.style.width = '0px';
                }
            }
        });
    </script>
@endsection
