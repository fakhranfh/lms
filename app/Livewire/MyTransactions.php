<?php

namespace App\Livewire;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Services\TierChangeService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class MyTransactions extends Component
{
    use WithPagination;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $status = null;

    public ?string $search = null;

    public int $perPage = 15;

    public string $sort = 'created_at';

    public string $direction = 'desc';

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
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
        if (in_array($property, ['dateFrom', 'dateTo', 'status', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function cancelTransaction(string $transactionId, TierChangeService $tierChangeService): void
    {
        $transaction = PaymentTransaction::where('initiated_by', auth()->id())->findOrFail($transactionId);

        $school = $transaction->school;

        abort_unless($school, 404);

        if ($tierChangeService->cancelTierChange($school)) {
            session()->flash('success', 'Tier change cancelled.');
        } else {
            $this->addError('transaction', 'No pending tier change to cancel.');
        }
    }

    private function query()
    {
        return PaymentTransaction::query()
            ->where('initiated_by', auth()->id())
            ->with(['detail.school', 'paymentGateway.paymentGatewayType'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('transaction_id', 'like', "%{$this->search}%")
                        ->orWhereHas('detail.school', fn ($s) => $s->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderBy($this->sort, $this->direction);
    }

    public function render()
    {
        /** @var LengthAwarePaginator $transactions */
        $transactions = $this->query()->paginate($this->perPage);

        return view('livewire.my-transactions', [
            'transactions' => $transactions,
            'statuses' => PaymentStatus::cases(),
            'sort' => $this->sort,
            'direction' => $this->direction,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Transactions'])
            ->section('app-content');
    }
}
