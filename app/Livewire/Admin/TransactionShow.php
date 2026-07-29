<?php

namespace App\Livewire\Admin;

use App\Enums\RoleName;
use App\Models\PaymentTransaction;
use Livewire\Component;

class TransactionShow extends Component
{
    public PaymentTransaction $transaction;

    public function mount(PaymentTransaction $transaction): void
    {
        abort_unless(auth()->user()->hasRole(RoleName::Admin), 403);

        $this->transaction = $transaction->load([
            'initiatedBy',
            'paymentGateway.paymentGatewayType',
            'detail.school',
            'detail.subscription',
            'detail.fromTier',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.transaction-show', [
            'transaction' => $this->transaction,
        ])
            ->extends('layouts.admin', ['topbarTitle' => 'Transaction Detail'])
            ->section('admin-content');
    }
}
