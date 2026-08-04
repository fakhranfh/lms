<?php

namespace App\Services;

use App\Models\SyllabusClassPolicy;
use App\Repositories\SyllabusClassPolicy\SyllabusClassPolicyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SyllabusClassPolicyService
{
    public function __construct(
        private SyllabusClassPolicyRepositoryInterface $syllabusClassPolicyRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->syllabusClassPolicyRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?SyllabusClassPolicy
    {
        return $this->syllabusClassPolicyRepository->find($id, $with);
    }

    public function create(array $data): SyllabusClassPolicy
    {
        return $this->syllabusClassPolicyRepository->create($data);
    }

    public function update(string $id, array $data): SyllabusClassPolicy
    {
        return $this->syllabusClassPolicyRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->syllabusClassPolicyRepository->delete($id);
    }
}
