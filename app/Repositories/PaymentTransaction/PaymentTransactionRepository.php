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

    /**
     * @return Collection<string, \stdClass&object{status: PaymentStatus, total_count: int, total_amount: float}>
     */
    public function getStatusTotals(?string $dateFrom, ?string $dateTo): Collection
    {
        return PaymentTransaction::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->selectRaw('status, count(*) as total_count, coalesce(sum(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->map(fn (PaymentTransaction $row): \stdClass => (object) [
                'status' => $row->status,
                'total_count' => (int) $row->getAttribute('total_count'),
                'total_amount' => (float) $row->getAttribute('total_amount'),
            ])
            ->keyBy(fn (\stdClass $row): string => $row->status->value);
    }

    /**
     * @return Collection<int|string, array{count: int<0, max>, total_amount: float}>
     */
    public function getGatewayTotals(?string $dateFrom, ?string $dateTo): Collection
    {
        return PaymentTransaction::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->where('status', PaymentStatus::Completed)
            ->with('paymentGateway.paymentGatewayType')
            ->get()
            ->groupBy(fn (PaymentTransaction $transaction): string => $transaction->paymentGateway?->paymentGatewayType->label ?? 'Unknown')
            ->map(fn (Collection $transactions): array => [
                'count' => count($transactions),
                'total_amount' => (float) $transactions->sum('amount'),
            ])
            ->sortByDesc('total_amount');
    }
}
