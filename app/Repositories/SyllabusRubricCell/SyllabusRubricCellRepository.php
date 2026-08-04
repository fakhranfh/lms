<?php

namespace App\Repositories\SyllabusRubricCell;

use App\Models\SyllabusRubricCell;
use Illuminate\Database\Eloquent\Collection;

class SyllabusRubricCellRepository implements SyllabusRubricCellRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = SyllabusRubricCell::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?SyllabusRubricCell
    {
        return SyllabusRubricCell::with($with)->find($id);
    }

    public function create(array $data): SyllabusRubricCell
    {
        return SyllabusRubricCell::create($data);
    }

    public function update(string $id, array $data): SyllabusRubricCell
    {
        $model = SyllabusRubricCell::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return SyllabusRubricCell::destroy($id);
    }
}
