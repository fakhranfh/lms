<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Services\TransactionSummaryService;
use Livewire\Component;

class TransactionSummary extends Component
{
    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole(RoleName::Admin), 403);
    }

    public function render(TransactionSummaryService $transactionSummaryService)
    {
        $byStatus = $transactionSummaryService->getStatusTotals($this->dateFrom, $this->dateTo);
        $byGateway = $transactionSummaryService->getGatewayTotals($this->dateFrom, $this->dateTo);

        $totalRevenue = $byStatus->get(PaymentStatus::Completed->value)->total_amount ?? 0;
        $totalTransactions = $byStatus->sum('total_count');

        return view('livewire.admin.transaction-summary', [
            'totalRevenue' => $totalRevenue,
            'totalTransactions' => $totalTransactions,
            'byStatus' => $byStatus,
            'byGateway' => $byGateway,
            'statuses' => PaymentStatus::cases(),
        ])
            ->extends('layouts.admin', ['topbarTitle' => 'Transaction Summary'])
            ->section('admin-content');
    }
}
