<?php

namespace App\Repositories\PricingTier;

use App\Models\PricingTier;
use App\Models\TierFeature;
use App\Models\TierLimit;
use Illuminate\Database\Eloquent\Collection;

class PricingTierRepository implements PricingTierRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = [])
    {
        $query = PricingTier::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->query($filters)->with($with)->get();
    }

    public function getAll(): Collection
    {
        return PricingTier::all();
    }

    public function find(int $id): ?PricingTier
    {
        return PricingTier::find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PricingTier
    {
        return PricingTier::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'] ?? 0,
            'currency' => $data['currency'] ?? 'IDR',
            'billing_period' => $data['billing_period'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PricingTier
    {
        $tier = PricingTier::findOrFail($id);
        $tier->update([
            'name' => $data['name'] ?? $tier->name,
            'description' => $data['description'] ?? $tier->description,
            'price' => $data['price'] ?? $tier->price,
            'currency' => $data['currency'] ?? $tier->currency,
            'billing_period' => $data['billing_period'] ?? $tier->billing_period,
            'is_active' => $data['is_active'] ?? $tier->is_active,
        ]);

        return $tier;
    }

    public function delete(int $id): int
    {
        return PricingTier::destroy($id);
    }

    /**
     * @param  array<int, array{feature_key: string, is_enabled: bool}>  $features
     */
    public function syncFeatures(PricingTier $tier, array $features): void
    {
        $tier->features()->delete();

        if (empty($features)) {
            return;
        }

        $featuresToInsert = array_map(function ($feature) use ($tier) {
            return [
                'pricing_tier_id' => $tier->id,
                'feature_key' => $feature['feature_key'],
                'is_enabled' => $feature['is_enabled'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $features);

        TierFeature::insert($featuresToInsert);
    }

    /**
     * @param  array<int, array{limit_key: string, limit_value: int|null}>  $limits
     */
    public function syncLimits(PricingTier $tier, array $limits): void
    {
        $tier->limits()->delete();

        if (empty($limits)) {
            return;
        }

        $limitsToInsert = array_map(function ($limit) use ($tier) {
            return [
                'pricing_tier_id' => $tier->id,
                'limit_key' => $limit['limit_key'],
                'limit_value' => $limit['limit_value'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $limits);

        TierLimit::insert($limitsToInsert);
    }
}
