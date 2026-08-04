<?php

namespace App\Services;

use App\Models\SessionSubtopic;
use App\Repositories\SessionSubtopic\SessionSubtopicRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SessionSubtopicService
{
    public function __construct(
        private SessionSubtopicRepositoryInterface $sessionSubtopicRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->sessionSubtopicRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?SessionSubtopic
    {
        return $this->sessionSubtopicRepository->find($id, $with);
    }

    public function create(array $data): SessionSubtopic
    {
        return $this->sessionSubtopicRepository->create($data);
    }

    public function update(string $id, array $data): SessionSubtopic
    {
        return $this->sessionSubtopicRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->sessionSubtopicRepository->delete($id);
    }
}
