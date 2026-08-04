<?php

namespace App\Services;

use App\Models\Session;
use App\Repositories\Session\SessionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SessionService
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->sessionRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Session
    {
        return $this->sessionRepository->find($id, $with);
    }

    public function create(array $data): Session
    {
        return $this->sessionRepository->create($data);
    }

    public function update(string $id, array $data): Session
    {
        return $this->sessionRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->sessionRepository->delete($id);
    }

    public function forCourse(string $courseId, array $with = []): Collection
    {
        return $this->sessionRepository->forCourse($courseId, $with);
    }
}
