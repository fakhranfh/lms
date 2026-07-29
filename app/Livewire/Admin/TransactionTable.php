<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Enums\TransactionType;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionTable extends Component
{
    use WithPagination;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $status = null;

    public ?string $transactionType = null;

    public ?string $gatewayId = null;

    public ?string $search = null;

    public int $perPage = 15;

    public string $sort = 'created_at';

    public string $direction = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole(RoleName::Admin), 403);
    }

    public function sortBy(string $field): void
    {
        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'status', 'transactionType', 'gatewayId', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    private function query()
    {
        return PaymentTransaction::query()
            ->with(['initiatedBy', 'paymentGateway.paymentGatewayType', 'detail.school'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->transactionType, fn ($q) => $q->where('transaction_type', $this->transactionType))
            ->when($this->gatewayId, fn ($q) => $q->where('payment_gateway_id', $this->gatewayId))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('transaction_id', 'like', "%{$this->search}%")
                        ->orWhereHas('initiatedBy', fn ($u) => $u->where('email', 'like', "%{$this->search}%"))
                        ->orWhereHas('detail.school', fn ($s) => $s->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderBy($this->sort, $this->direction);
    }

    public function render()
    {
        /** @var LengthAwarePaginator $transactions */
        $transactions = $this->query()->paginate($this->perPage);

        return view('livewire.admin.transaction-table', [
            'transactions' => $transactions,
            'gateways' => PaymentGateway::with('paymentGatewayType')->get(),
            'statuses' => PaymentStatus::cases(),
            'transactionTypes' => TransactionType::cases(),
            'sort' => $this->sort,
            'direction' => $this->direction,
        ])
            ->extends('layouts.admin', ['topbarTitle' => 'Transactions'])
            ->section('admin-content');
    }
}
