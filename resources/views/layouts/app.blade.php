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
        x-data="{ open: false, itemId: null, itemIds: [], itemName: null, itemType: null }"
        x-on:open-delete-confirm.window="open = true; itemId = $event.detail.id ?? null; itemIds = $event.detail.ids ?? []; itemName = $event.detail.name ?? null; itemType = $event.detail.type ?? null"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-gutter"
        @click.self="open = false"
    >
        <div class="bg-surface border border-outline-variant rounded-lg p-space-lg max-w-sm w-full space-y-space-lg">
            <h2 class="font-headline-sm text-headline-sm text-on-surface" x-text="itemType === 'gradebook-scores' ? 'Reset gradebook scores' : 'Delete confirmation'"></h2>
            <template x-if="itemType === 'gradebook-scores'">
                <p class="font-body-md text-body-md text-secondary">
                    Are you sure you want to reset all gradebook scores for this course? This action cannot be undone.
                </p>
            </template>
            <template x-if="itemType === 'courses-bulk'">
                <p class="font-body-md text-body-md text-secondary">
                    Are you sure you want to delete <span class="font-medium" x-text="itemIds.length"></span> selected course<span x-show="itemIds.length !== 1">s</span>? This will also delete all of their sessions. This action cannot be undone.
                </p>
            </template>
            <template x-if="itemType !== 'gradebook-scores' && itemType !== 'courses-bulk'">
                <p class="font-body-md text-body-md text-secondary">
                    Are you sure you want to delete
                    <template x-if="itemName"><span>"<span class="font-medium" x-text="itemName"></span>"</span></template>
                    <template x-if="!itemName"><span>this item</span></template>?
                    This action cannot be undone.
                </p>
            </template>
            <p x-show="itemType === 'courses'" class="font-body-sm text-body-sm text-error">
                This will also delete all of its sessions.
            </p>
            <div class="flex items-center justify-end gap-space-md">
                <button type="button" @click="open = false" class="px-space-lg py-space-sm font-label-md text-label-md text-secondary hover:underline">Cancel</button>
                <button
                    type="button"
                    @click="itemType === 'courses-bulk' ? Livewire.dispatch('bulk-delete-confirmed', { ids: itemIds }) : Livewire.dispatch('delete-confirmed', { id: itemId }); open = false"
                    class="px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                    x-text="itemType === 'gradebook-scores' ? 'Reset' : 'Delete'"
                >
                </button>
            </div>
        </div>
    </div>

    <x-ui.error-snackbar />

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
