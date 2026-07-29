<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Repositories\PaymentTransaction\PaymentTransactionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TransactionSummaryService
{
    private const CACHE_PREFIX = 'transaction_summary';

    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly PaymentTransactionRepositoryInterface $paymentTransactionRepository,
    ) {}

    /**
     * @return Collection<string, object{status: PaymentStatus, total_count: int, total_amount: float}>
     */
    public function getStatusTotals(?string $dateFrom, ?string $dateTo): Collection
    {
        return Cache::remember(
            $this->cacheKey('status', $dateFrom, $dateTo),
            self::CACHE_TTL,
            fn () => $this->paymentTransactionRepository->getStatusTotals($dateFrom, $dateTo)
        );
    }

    /**
     * @return Collection<string, array{count: int, total_amount: float}>
     */
    public function getGatewayTotals(?string $dateFrom, ?string $dateTo): Collection
    {
        return Cache::remember(
            $this->cacheKey('gateway', $dateFrom, $dateTo),
            self::CACHE_TTL,
            fn () => $this->paymentTransactionRepository->getGatewayTotals($dateFrom, $dateTo)
        );
    }

    public function clearCache(?string $dateFrom = null, ?string $dateTo = null): void
    {
        Cache::forget($this->cacheKey('status', $dateFrom, $dateTo));
        Cache::forget($this->cacheKey('gateway', $dateFrom, $dateTo));
    }

    private function cacheKey(string $metric, ?string $dateFrom, ?string $dateTo): string
    {
        return self::CACHE_PREFIX.":{$metric}:".($dateFrom ?? 'all').':'.($dateTo ?? 'all');
    }
}
