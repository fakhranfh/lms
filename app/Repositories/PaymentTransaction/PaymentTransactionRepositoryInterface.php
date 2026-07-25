<?php

namespace App\Repositories\PaymentTransaction;

use App\Models\PaymentTransaction;

interface PaymentTransactionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PaymentTransaction;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): PaymentTransaction;

    /**
     * Find the pending transaction for a subscription (school tier), if any.
     */
    public function findPendingBySubscriptionId(string $subscriptionId): ?PaymentTransaction;

    /**
     * Find the most recently completed transaction for a school, if any.
     */
    public function findLatestCompletedForSchool(string $schoolId): ?PaymentTransaction;
}
