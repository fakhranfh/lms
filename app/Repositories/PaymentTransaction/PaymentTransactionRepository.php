<?php

namespace App\Repositories\PaymentTransaction;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;

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
}
