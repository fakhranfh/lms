<?php

namespace App\Repositories\PaymentTransaction;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use Illuminate\Support\Collection;

class PaymentTransactionRepository implements PaymentTransactionRepositoryInterface
{
    public function create(array $data): PaymentTransaction
    {
        return PaymentTransaction::create($data);
    }

    public function update(string $id, array $data): PaymentTransaction
    {
        $transaction = PaymentTransaction::findOrFail($id);
        $transaction->update($data);

        return $transaction;
    }

    public function findPendingBySubscriptionId(string $subscriptionId): ?PaymentTransaction
    {
        return PaymentTransaction::whereHas('detail', function ($query) use ($subscriptionId) {
            $query->where('subscription_id', $subscriptionId);
        })
            ->where('status', PaymentStatus::Pending)
            ->first();
    }

    public function findLatestCompletedForSchool(string $schoolId): ?PaymentTransaction
    {
        return PaymentTransaction::whereHas('detail', function ($query) use ($schoolId) {
            $query->where('school_id', $schoolId);
        })
            ->where('status', PaymentStatus::Completed)
            ->latest('created_at')
            ->first();
    }

    public function findPendingRegistrationForUser(string $userId): ?PaymentTransaction
    {
        return PaymentTransaction::whereHas('detail', function ($query) {
            $query->whereNotNull('registration_data');
        })
            ->where('initiated_by', $userId)
            ->where('status', PaymentStatus::Pending)
            ->latest('created_at')
            ->first();
    }

    public function getStatusTotals(?string $dateFrom, ?string $dateTo): Collection
    {
        return PaymentTransaction::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->selectRaw('status, count(*) as total_count, coalesce(sum(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status->value);
    }

    public function getGatewayTotals(?string $dateFrom, ?string $dateTo): Collection
    {
        return PaymentTransaction::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->where('status', PaymentStatus::Completed)
            ->with('paymentGateway.paymentGatewayType')
            ->get()
            ->groupBy(fn (PaymentTransaction $transaction) => $transaction->paymentGateway?->paymentGatewayType?->label ?? 'Unknown')
            ->map(fn (Collection $transactions) => [
                'count' => $transactions->count(),
                'total_amount' => (float) $transactions->sum('amount'),
            ])
            ->sortByDesc('total_amount');
    }
}
