<?php

namespace App\Repositories\PaymentTransaction;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use Illuminate\Support\Collection;

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

    /**
     * Find the most recent unpaid school-registration transaction initiated
     * by the given user, if any.
     */
    public function findPendingRegistrationForUser(string $userId): ?PaymentTransaction;

    /**
     * Get transaction counts and amount totals grouped by status, optionally
     * scoped to a date range.
     *
     * @return Collection<string, \stdClass&object{status: PaymentStatus, total_count: int, total_amount: float}>
     */
    public function getStatusTotals(?string $dateFrom, ?string $dateTo): Collection;

    /**
     * Get completed transaction counts and amount totals grouped by gateway
     * type label, optionally scoped to a date range.
     *
     * @return Collection<int|string, array{count: int<0, max>, total_amount: float}>
     */
    public function getGatewayTotals(?string $dateFrom, ?string $dateTo): Collection;
}
