<?php

namespace App\Repositories\School;

use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SchoolRepository implements SchoolRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = [])
    {
        $query = School::with($filters['with'] ?? []);

        if (isset($filters['search']) && $filters['search']) {
            $query->whereRaw('name ILIKE ?', ["%{$filters['search']}%"])
                ->orWhereRaw('domain ILIKE ?', ["%{$filters['search']}%"]);
        }

        if (isset($filters['sort']) && isset($filters['direction'])) {
            $query->orderBy($filters['sort'], $filters['direction']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['with'] = $with;

        return $this->query($filters)->paginate($perPage);
    }

    public function getAll(): Collection
    {
        return School::all();
    }

    public function find(string $id): ?School
    {
        return School::find($id);
    }

    public function findByDomain(string $domain): ?School
    {
        return School::where('domain', $domain)->first();
    }

    /**
     * @param  array<string>  $with
     */
    public function findWith(string $id, array $with = []): ?School
    {
        return School::with($with)->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): School
    {
        return School::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): School
    {
        $school = School::findOrFail($id);
        $school->update($data);

        return $school;
    }

    public function delete(string $id): int
    {
        return School::destroy($id);
    }

    public function attachAdmin(School $school, string $userId): void
    {
        $school->admins()->syncWithoutDetaching([$userId]);
    }

    public function administers(School $school, string $userId): bool
    {
        return $school->admins()->whereKey($userId)->exists();
    }

    /**
     * @return Collection<int, School>
     */
    public function getAllWithUserCounts(): Collection
    {
        return School::withCount('users')->get();
    }
}
