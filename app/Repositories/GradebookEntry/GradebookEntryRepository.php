<?php

namespace App\Repositories\GradebookEntry;

use App\Models\GradebookEntry;
use Illuminate\Database\Eloquent\Collection;

class GradebookEntryRepository implements GradebookEntryRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = GradebookEntry::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?GradebookEntry
    {
        return GradebookEntry::with($with)->find($id);
    }

    public function create(array $data): GradebookEntry
    {
        return GradebookEntry::create($data);
    }

    public function update(string $id, array $data): GradebookEntry
    {
        $model = GradebookEntry::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return GradebookEntry::destroy($id);
    }

    public function forCourseAndUser(string $courseId, string $userId): Collection
    {
        return GradebookEntry::where('course_id', $courseId)->where('user_id', $userId)->get();
    }
}
