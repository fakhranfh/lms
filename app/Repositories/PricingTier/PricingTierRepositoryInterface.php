<?php

namespace App\Repositories\PricingTier;

use App\Models\PricingTier;
use App\Models\TierLimit;
use Illuminate\Database\Eloquent\Collection;

interface PricingTierRepositoryInterface
{
    /**
     * Get pricing tiers with optional filters and eager-loaded relationships.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection;

    /**
     * Get all pricing tiers.
     */
    public function getAll(): Collection;

    /**
     * Find a pricing tier by ID.
     */
    public function find(int $id): ?PricingTier;

    /**
     * Create a new pricing tier.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PricingTier;

    /**
     * Update a pricing tier.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PricingTier;

    /**
     * Delete a pricing tier.
     */
    public function delete(int $id): int;

    /**
     * Sync limits for a pricing tier (replace-all pattern).
     *
     * @param  array<int, array{limit_key: string, limit_value: int|null}>  $limits
     */
    public function syncLimits(PricingTier $tier, array $limits): void;

    /**
     * Find a tier's limit row by key, if set.
     */
    public function findLimit(int $tierId, string $limitKey): ?TierLimit;
}
