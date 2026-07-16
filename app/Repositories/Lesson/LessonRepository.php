<?php

namespace App\Repositories\Lesson;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LessonRepository implements LessonRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Lesson::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Lesson
    {
        return Lesson::with($with)->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lesson
    {
        return Lesson::create([
            'module_id' => $data['module_id'],
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'video_embed_url' => $data['video_embed_url'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'order' => $data['order'] ?? $this->getNextOrder($data['module_id']),
            'is_published' => $data['is_published'] ?? false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Lesson
    {
        $lesson = Lesson::findOrFail($id);

        $lesson->update([
            'title' => $data['title'] ?? $lesson->title,
            'content' => $data['content'] ?? $lesson->content,
            'video_embed_url' => $data['video_embed_url'] ?? $lesson->video_embed_url,
            'duration_minutes' => $data['duration_minutes'] ?? $lesson->duration_minutes,
            'is_published' => $data['is_published'] ?? $lesson->is_published,
        ]);

        return $lesson;
    }

    public function delete(string $id): int
    {
        return Lesson::destroy($id);
    }

    public function getNextOrder(string $moduleId): int
    {
        return (Lesson::where('module_id', $moduleId)->max('order') ?? 0) + 1;
    }

    public function moveUp(string $id): void
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->moveUp();
    }

    public function moveDown(string $id): void
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->moveDown();
    }

    public function publish(string $id): void
    {
        Lesson::findOrFail($id)->update(['is_published' => true]);
    }

    public function unpublish(string $id): void
    {
        Lesson::findOrFail($id)->update(['is_published' => false]);
    }

    public function markComplete(string $lessonId, string $userId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $lesson->markCompleteFor(new User(['id' => $userId]));
    }

    public function isCompletedBy(string $lessonId, string $userId): bool
    {
        $lesson = Lesson::findOrFail($lessonId);

        return $lesson->isCompletedBy(new User(['id' => $userId]));
    }
}
