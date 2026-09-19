@props([
    'entity',
    'permissionPrefix',
    'items',
    'loaded' => false,
    'sort' => 'name',
    'direction' => 'asc',
    'search' => null,
    'successMessage' => null,
    'errorMessage' => null,
    'regeneratedLoginUrl' => null,
    'generateComponent' => null,
])

<div class="w-full space-y-space-lg" wire:init="loadUsers" x-data="{ showGenerateModal: false }" x-init="$wire.on('{{ $permissionPrefix }}-generated', () => showGenerateModal = false)">
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
        <h1 class="font-headline-sm text-headline-sm text-on-surface">{{ ucfirst($permissionPrefix) }}</h1>
        <div class="flex items-center gap-space-md">
            @if ($generateComponent && app()->environment(['local', 'testing']))
                @can("{$permissionPrefix}.create")
                    <button type="button" @click="showGenerateModal = true"
                        class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs">
                        <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                        Generate {{ ucfirst($permissionPrefix) }}
                    </button>
                @endcan
            @endif
            @can("{$permissionPrefix}.view")
                <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                    class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs disabled:opacity-60">
                    <span wire:loading wire:target="exportExcel"><x-ui.spinner /></span>
                    <span wire:loading.remove wire:target="exportExcel" class="material-symbols-outlined text-[18px]">grid_on</span>
                    <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                    <span wire:loading wire:target="exportExcel">Exporting...</span>
                </button>
                <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                    class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs disabled:opacity-60">
                    <span wire:loading wire:target="exportPdf"><x-ui.spinner /></span>
                    <span wire:loading.remove wire:target="exportPdf" class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                    <span wire:loading.remove wire:target="exportPdf">Export PDF</span>
                    <span wire:loading wire:target="exportPdf">Exporting...</span>
                </button>
            @endcan
            @can("{$permissionPrefix}.edit")
                <a href="{{ route("{$permissionPrefix}.photos") }}"
                    class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs">
                    <span class="material-symbols-outlined text-[18px]">add_a_photo</span>
                    Bulk Upload Photos
                </a>
            @endcan
            @can("{$permissionPrefix}.import")
                <a href="{{ route("{$permissionPrefix}.import") }}"
                    class="px-space-lg py-space-sm border border-outline-variant text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container transition-colors inline-flex items-center gap-space-2xs">
                    <span class="material-symbols-outlined text-[18px]">upload_file</span>
                    Import {{ ucfirst($permissionPrefix) }}
                </a>
            @endcan
            @can("{$permissionPrefix}.create")
                <a href="{{ route("{$permissionPrefix}.create") }}" class="px-space-lg py-space-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">Create {{ ucfirst($entity) }}</a>
            @endcan
        </div>
    </div>

    @if ($loaded)
        <x-ui.pagination-links
            :paginator="$items"
            perPageModel="perPage"
            :perPageOptions="[10, 15, 25, 50]"
            searchModel="search"
            searchPlaceholder="Search by name or email..."
            :search="$search"
        />
    @endif

    <x-users.table
        :items="$items"
        :loaded="$loaded"
        :sort="$sort"
        :direction="$direction"
        :entity="$entity"
        :permission-prefix="$permissionPrefix"
    />

    @if ($loaded)
        <x-ui.pagination-links
            :paginator="$items"
            perPageModel="perPage"
            :perPageOptions="[10, 15, 25, 50]"
        />
    @endif

    <!-- Regenerated Login Link Modal -->
    @if ($regeneratedLoginUrl)
        <div wire:key="login-url-modal-{{ md5($regeneratedLoginUrl) }}" x-data="{ copied: false, show: true }">
            <x-ui.modal show="show" onClose="show = false" maxWidth="max-w-lg">
                <div class="bg-surface border border-outline-variant rounded-lg shadow-lg p-space-lg space-y-space-md">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">New Login Link Generated</h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Share this one-time login link with the {{ $entity }}:</p>
                    <div class="flex items-center gap-space-sm">
                        <input type="text" readonly value="{{ $regeneratedLoginUrl }}" x-ref="loginUrlInput"
                            class="flex-1 px-space-md py-space-sm border border-outline-variant rounded-lg font-body-sm text-body-sm bg-surface">
                        <button type="button"
                            @click="navigator.clipboard.writeText($refs.loginUrlInput.value); copied = true; setTimeout(() => copied = false, 2000)"
                            class="px-space-md py-space-sm bg-primary text-on-primary rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity">
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                    <div class="flex justify-end pt-space-sm">
                        <button type="button" @click="show = false" class="px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition">
                            Close
                        </button>
                    </div>
                </div>
            </x-ui.modal>
        </div>
    @endif

    <!-- Generate Modal -->
    @if ($generateComponent && app()->environment(['local', 'testing']))
        @can("{$permissionPrefix}.create")
            <x-ui.modal show="showGenerateModal" onClose="showGenerateModal = false" maxWidth="max-w-2xl">
                <div class="bg-surface border border-outline-variant rounded-lg shadow-lg p-space-lg">
                    @livewire($generateComponent, ['embedded' => true], key("{$entity}-generate-modal"))
                </div>
            </x-ui.modal>
        @endcan
    @endif
</div>
