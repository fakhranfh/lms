<?php

namespace App\Services;

use App\Models\PricingTier;
use App\Repositories\PricingTier\PricingTierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PricingTierService
{
    public function __construct(protected PricingTierRepositoryInterface $pricingTierRepository) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->pricingTierRepository->get($filters, $with);
    }

    public function getAll(): Collection
    {
        return $this->pricingTierRepository->getAll();
    }

    public function find(int $id): ?PricingTier
    {
        return $this->pricingTierRepository->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PricingTier
    {
        $tier = $this->pricingTierRepository->create($data);

        $this->pricingTierRepository->syncFeatures($tier, $data['features'] ?? []);
        $this->pricingTierRepository->syncLimits($tier, $data['limits'] ?? []);

        return $tier;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PricingTier
    {
        $tier = $this->pricingTierRepository->update($id, $data);

        $this->pricingTierRepository->syncFeatures($tier, $data['features'] ?? []);
        $this->pricingTierRepository->syncLimits($tier, $data['limits'] ?? []);

        return $tier;
    }

    public function delete(int $id): int
    {
        $tier = $this->pricingTierRepository->find($id);

        if ($tier && $tier->schoolTiers()->exists()) {
            throw ValidationException::withMessages([
                'tier' => __('Cannot delete a pricing tier that is currently assigned to schools.'),
            ]);
        }

        return $this->pricingTierRepository->delete($id);
    }
}
