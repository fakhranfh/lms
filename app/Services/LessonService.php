<?php

namespace App\Services;

use App\Models\Lesson;
use App\Repositories\Lesson\LessonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LessonService
{
    public function __construct(
        private LessonRepositoryInterface $lessonRepository
    ) {}

    /**
     * Get lessons with optional filters and relations.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->lessonRepository->get($filters, $with);
    }

    /**
     * Find a lesson by ID.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Lesson
    {
        return $this->lessonRepository->find($id, $with);
    }

    /**
     * Create a new lesson.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lesson
    {
        if (isset($data['video_embed_url'])) {
            $data['video_embed_url'] = Lesson::convertToEmbedUrl($data['video_embed_url']);
        }

        return $this->lessonRepository->create($data);
    }

    /**
     * Update a lesson.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Lesson
    {
        if (isset($data['video_embed_url'])) {
            $data['video_embed_url'] = Lesson::convertToEmbedUrl($data['video_embed_url']);
        }

        return $this->lessonRepository->update($id, $data);
    }

    /**
     * Delete a lesson.
     */
    public function delete(string $id): int
    {
        return $this->lessonRepository->delete($id);
    }

    /**
     * Get next order for a module.
     */
    public function getNextOrder(string $moduleId): int
    {
        return $this->lessonRepository->getNextOrder($moduleId);
    }

    /**
     * Move a lesson up in the order.
     */
    public function moveUp(string $id): void
    {
        $this->lessonRepository->moveUp($id);
    }

    /**
     * Move a lesson down in the order.
     */
    public function moveDown(string $id): void
    {
        $this->lessonRepository->moveDown($id);
    }

    /**
     * Publish a lesson.
     */
    public function publish(string $id): void
    {
        $this->lessonRepository->publish($id);
    }

    /**
     * Unpublish a lesson.
     */
    public function unpublish(string $id): void
    {
        $this->lessonRepository->unpublish($id);
    }

    /**
     * Mark lesson as completed by user.
     */
    public function markComplete(string $lessonId, string $userId): void
    {
        $this->lessonRepository->markComplete($lessonId, $userId);
    }

    /**
     * Check if lesson is completed by user.
     */
    public function isCompletedBy(string $lessonId, string $userId): bool
    {
        return $this->lessonRepository->isCompletedBy($lessonId, $userId);
    }
}
