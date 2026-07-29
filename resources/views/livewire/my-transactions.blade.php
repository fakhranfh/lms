@section('title', 'Transactions')

<div class="space-y-space-lg" x-data="{ cancelId: null, showModal: false }">
    <div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Transactions</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">Payments you've initiated for your schools</p>
    </div>

    @if (session('success'))
        <div class="p-space-lg bg-success/10 border border-success/20 rounded-lg">
            <p class="font-body-md text-body-md text-success">{{ session('success') }}</p>
        </div>
    @endif

    @error('transaction')
        <div class="p-space-lg bg-error/10 border border-error/20 rounded-lg">
            <p class="font-body-md text-body-md text-error">{{ $message }}</p>
        </div>
    @enderror

    <div class="grid grid-cols-1 md:grid-cols-4 gap-space-md">
        <input type="date" wire:model.live="dateFrom" placeholder="From" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <input type="date" wire:model.live="dateTo" placeholder="To" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <select wire:model.live="status" class="px-space-md py-space-sm border border-outline rounded-lg">
            <option value="">All statuses</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </select>
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search transaction ID or school" class="px-space-md py-space-sm border border-outline rounded-lg" />
    </div>

    <x-transactions.table
        :transactions="$transactions"
        :perPage="$perPage"
        :sort="$sort"
        :direction="$direction"
        filterTargets="dateFrom,dateTo,status,search,perPage"
        actionsView="components.transactions.school-actions"
    />

    <!-- Cancel Tier Change Modal -->
    <div
        x-show="showModal"
        x-cloak
        class="fixed inset-0 z-50"
    >
        <div
            @click="showModal = false"
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        <div
            class="fixed inset-0 flex items-center justify-center p-4"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <div class="bg-surface border border-outline-variant rounded-lg shadow-lg max-w-sm w-full">
                <div class="p-space-lg space-y-space-lg">
                    <div class="flex justify-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-error/10 rounded-full">
                            <span class="material-symbols-outlined text-error text-[24px]" data-weight="fill">cancel</span>
                        </div>
                    </div>

                    <div class="text-center space-y-space-sm">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Cancel Tier Change</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Are you sure you want to cancel this tier change? You'll need to start a new upgrade from My Schools.</p>
                    </div>

                    <div class="flex gap-space-md pt-space-md">
                        <button
                            @click="showModal = false"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm border border-outline rounded-lg font-label-md text-label-md text-on-surface hover:bg-surface-container transition"
                        >
                            Keep
                        </button>
                        <button
                            @click="showModal = false; $wire.call('cancelTransaction', cancelId)"
                            type="button"
                            class="flex-1 px-space-lg py-space-sm bg-error text-on-error rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity"
                        >
                            Cancel Tier Change
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
