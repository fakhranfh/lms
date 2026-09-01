<?php

namespace App\Services;

use App\Models\Session;
use App\Repositories\Session\SessionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SessionService
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
        private ForumService $forumService,
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
        return DB::transaction(function () use ($data) {
            $data['order'] ??= $this->sessionRepository->nextOrder($data['course_id']);

            $session = $this->sessionRepository->create($data);

            $this->forumService->create([
                'course_id' => $session->course_id,
                'session_id' => $session->id,
                'title' => null,
                'created_by' => auth()->id(),
            ]);

            return $session;
        });
    }

    public function update(string $id, array $data): Session
    {
        return $this->sessionRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->sessionRepository->delete($id);
    }

    /**
     * @param  array<string>  $ids
     */
    public function deleteMany(array $ids): int
    {
        return $this->sessionRepository->deleteMany($ids);
    }

    public function deleteAllForCourse(string $courseId): int
    {
        return $this->sessionRepository->deleteForCourse($courseId);
    }

    /**
     * @param  array<string>  $orderedIds
     */
    public function reorder(string $courseId, array $orderedIds): void
    {
        $this->sessionRepository->reorder($courseId, $orderedIds);
    }

    /**
     * @return Collection<int, Session>
     */
    public function forCourse(string $courseId, array $with = []): Collection
    {
        return $this->sessionRepository->forCourse($courseId, $with);
    }
}
