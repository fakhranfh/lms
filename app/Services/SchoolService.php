<?php

namespace App\Services;

use App\Models\PricingTier;
use App\Models\School;
use App\Models\SchoolTier;
use App\Models\TierChange;
use App\Repositories\School\SchoolRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SchoolService
{
    public function __construct(protected SchoolRepositoryInterface $schoolRepository) {}

    /**
     * Get paginated schools with filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->schoolRepository->paginate($filters, $with, $perPage);
    }

    /**
     * Get all schools.
     */
    public function getAll(): Collection
    {
        return $this->schoolRepository->getAll();
    }

    /**
     * Find a school by ID.
     */
    public function find(string $id)
    {
        return $this->schoolRepository->find($id);
    }

    /**
     * Find a school by ID with eager-loaded relationships.
     *
     * @param  array<string>  $with
     */
    public function findWith(string $id, array $with = [])
    {
        return $this->schoolRepository->findWith($id, $with);
    }

    /**
     * Create a new school.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): School
    {
        $basicTier = PricingTier::where('slug', 'free')->firstOrFail();

        $data['tier_id'] = $basicTier->id;
        $school = $this->schoolRepository->create($data);
        $this->assignDefaultTier($school);

        return $school;
    }

    /**
     * Assign default tier to a school.
     */
    public function assignDefaultTier(School $school): void
    {
        $basicTier = $school->tier;

        $schoolTier = SchoolTier::create([
            'school_id' => $school->id,
            'tier_id' => $basicTier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => null,
        ]);

        TierChange::create([
            'school_tier_id' => $schoolTier->id,
            'from_tier_id' => null,
            'to_tier_id' => $basicTier->id,
            'change_type' => 'initial',
            'prorated_amount' => 0,
            'changed_at' => now(),
        ]);
    }

    /**
     * Build the registration URL for a school.
     */
    public function buildRegisterUrl(School $school, string $scheme = 'http', ?int $port = null): string
    {
        $url = "{$scheme}://{$school->domain}/register";

        if ($port && ! in_array($port, [80, 443])) {
            $url = "{$scheme}://{$school->domain}:{$port}/register";
        }

        return $url;
    }
}
