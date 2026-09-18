@props([
    'transactions',
    'perPage' => 15,
    'sort' => 'created_at',
    'direction' => 'desc',
    'filterTargets' => 'dateFrom,dateTo,status,transactionType,gatewayId,search,perPage',
    'actionsView' => 'components.transactions.admin-actions',
])

<x-ui.livewire-data-table
    :columns="[
        ['key' => 'created_at', 'label' => 'Date'],
        ['key' => 'transaction_id', 'label' => 'Transaction ID', 'sortable' => false],
        ['key' => 'gateway', 'label' => 'Gateway', 'sortable' => false],
        ['key' => 'amount', 'label' => 'Amount'],
        ['key' => 'status', 'label' => 'Status', 'sortable' => false],
    ]"
    :items="$transactions"
    :sort="$sort"
    :direction="$direction"
    :perPage="$perPage"
    :loadingTarget="$filterTargets"
>
    @forelse ($transactions as $transaction)
        <tr wire:key="transaction-{{ $transaction->id }}" class="hover:bg-surface-container-lowest transition-colors">
            <td class="px-space-lg py-space-md text-body-sm text-on-surface-variant">{{ $transaction->created_at_display?->format('M j, Y g:i A') }}</td>
            <td class="px-space-lg py-space-md text-body-md text-on-surface">{{ $transaction->transaction_id }}</td>
            <td class="px-space-lg py-space-md text-body-md text-on-surface-variant">{{ $transaction->paymentGateway?->paymentGatewayType?->label ?? '—' }}</td>
            <td class="px-space-lg py-space-md text-body-md text-on-surface">Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</td>
            <td class="px-space-lg py-space-md">
                <span class="inline-block px-space-xs py-space-xxs rounded-full font-label-sm text-label-sm capitalize {{ $transaction->status->badgeClasses() }}">
                    {{ $transaction->status->label() }}
                </span>
            </td>
            <td class="px-space-lg py-space-md text-right space-x-space-sm whitespace-nowrap">
                @include($actionsView, ['transaction' => $transaction])
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-space-lg py-space-lg text-center text-on-surface-variant">
                No transactions found.
            </td>
        </tr>
    @endforelse
</x-ui.livewire-data-table>
