@section('title', 'Transactions')

<div class="space-y-space-lg">
    <div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Transactions</h1>
        <p class="text-body-sm text-on-surface-variant mt-1">All payment transactions across schools</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-space-md">
        <input type="date" wire:model.live="dateFrom" placeholder="From" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <input type="date" wire:model.live="dateTo" placeholder="To" class="px-space-md py-space-sm border border-outline rounded-lg" />
        <select wire:model.live="status" class="px-space-md py-space-sm border border-outline rounded-lg">
            <option value="">All statuses</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="transactionType" class="px-space-md py-space-sm border border-outline rounded-lg">
            <option value="">All types</option>
            @foreach ($transactionTypes as $typeOption)
                <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="gatewayId" class="px-space-md py-space-sm border border-outline rounded-lg">
            <option value="">All gateways</option>
            @foreach ($gateways as $gateway)
                <option value="{{ $gateway->id }}">{{ $gateway->paymentGatewayType?->label ?? $gateway->id }}</option>
            @endforeach
        </select>
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search transaction ID, school, user" class="px-space-md py-space-sm border border-outline rounded-lg" />
    </div>

    <x-transactions.table :transactions="$transactions" :perPage="$perPage" :sort="$sort" :direction="$direction" />
</div>
