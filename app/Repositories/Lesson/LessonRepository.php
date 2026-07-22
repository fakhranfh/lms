<?php

namespace App\Repositories\Lesson;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class LessonRepository implements LessonRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): EloquentCollection
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
     * @param  array<string>  $with
     */
    public function findOrFail(string $id, array $with = []): Lesson
    {
        return Lesson::with($with)->findOrFail($id);
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

    /**
     * Get all published lessons for a module, ordered by position.
     *
     * @param  array<string>  $with
     */
    public function getByModulePublished(string $moduleId, array $with = []): EloquentCollection
    {
        return Lesson::where('module_id', $moduleId)
            ->where('is_published', true)
            ->orderBy('order')
            ->with($with)
            ->get();
    }
}
