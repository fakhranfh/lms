<?php

namespace App\Repositories\Module;

use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;

class ModuleRepository implements ModuleRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Module::query();

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
    public function find(string $id, array $with = []): ?Module
    {
        return Module::with($with)->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Module
    {
        return Module::create([
            'course_id' => $data['course_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'order' => $data['order'] ?? $this->getNextOrder($data['course_id']),
            'is_published' => $data['is_published'] ?? false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Module
    {
        $module = Module::findOrFail($id);

        $module->update([
            'title' => $data['title'] ?? $module->title,
            'description' => $data['description'] ?? $module->description,
            'is_published' => $data['is_published'] ?? $module->is_published,
        ]);

        return $module;
    }

    public function delete(string $id): int
    {
        return Module::destroy($id);
    }

    public function getNextOrder(string $courseId): int
    {
        return (Module::where('course_id', $courseId)->max('order') ?? 0) + 1;
    }

    public function moveUp(string $id): void
    {
        $module = Module::findOrFail($id);
        $module->moveUp();
    }

    public function moveDown(string $id): void
    {
        $module = Module::findOrFail($id);
        $module->moveDown();
    }

    public function publish(string $id): void
    {
        Module::findOrFail($id)->update(['is_published' => true]);
    }

    public function unpublish(string $id): void
    {
        Module::findOrFail($id)->update(['is_published' => false]);
    }
}
