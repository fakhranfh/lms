<?php

namespace App\Repositories\GradebookSessionEntry;

use App\Models\GradebookSessionEntry;
use Illuminate\Database\Eloquent\Collection;

class GradebookSessionEntryRepository implements GradebookSessionEntryRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = GradebookSessionEntry::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?GradebookSessionEntry
    {
        return GradebookSessionEntry::with($with)->find($id);
    }

    public function create(array $data): GradebookSessionEntry
    {
        return GradebookSessionEntry::create($data);
    }

    public function update(string $id, array $data): GradebookSessionEntry
    {
        $model = GradebookSessionEntry::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return GradebookSessionEntry::destroy($id);
    }

    public function forGradebookEntry(string $gradebookEntryId): Collection
    {
        return GradebookSessionEntry::where('gradebook_entry_id', $gradebookEntryId)->get();
    }
}
