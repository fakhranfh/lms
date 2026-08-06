<?php

namespace App\Services;

use App\Models\Forum;
use App\Repositories\Forum\ForumRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ForumService
{
    public function __construct(
        private ForumRepositoryInterface $forumRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->forumRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Forum
    {
        return $this->forumRepository->find($id, $with);
    }

    public function create(array $data): Forum
    {
        return $this->forumRepository->create($data);
    }

    public function update(string $id, array $data): Forum
    {
        return $this->forumRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->forumRepository->delete($id);
    }

    public function findOrCreateForSession(string $sessionId, string $courseId): Forum
    {
        $existing = $this->forumRepository->findBySessionAndCourse($sessionId, $courseId);

        if ($existing) {
            return $existing;
        }

        return $this->forumRepository->create([
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'title' => null,
            'created_by' => auth()->id(),
        ]);
    }
}
