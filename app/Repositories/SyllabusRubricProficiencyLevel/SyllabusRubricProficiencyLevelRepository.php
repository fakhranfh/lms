<?php

namespace App\Repositories\SyllabusRubricProficiencyLevel;

use App\Models\SyllabusRubricProficiencyLevel;
use Illuminate\Database\Eloquent\Collection;

class SyllabusRubricProficiencyLevelRepository implements SyllabusRubricProficiencyLevelRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = SyllabusRubricProficiencyLevel::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?SyllabusRubricProficiencyLevel
    {
        return SyllabusRubricProficiencyLevel::with($with)->find($id);
    }

    public function create(array $data): SyllabusRubricProficiencyLevel
    {
        return SyllabusRubricProficiencyLevel::create($data);
    }

    public function update(string $id, array $data): SyllabusRubricProficiencyLevel
    {
        $model = SyllabusRubricProficiencyLevel::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return SyllabusRubricProficiencyLevel::destroy($id);
    }
}
