<?php

namespace App\Repositories\SyllabusRubricKeyIndicator;

use App\Models\SyllabusRubricKeyIndicator;
use Illuminate\Database\Eloquent\Collection;

class SyllabusRubricKeyIndicatorRepository implements SyllabusRubricKeyIndicatorRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = SyllabusRubricKeyIndicator::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?SyllabusRubricKeyIndicator
    {
        return SyllabusRubricKeyIndicator::with($with)->find($id);
    }

    public function create(array $data): SyllabusRubricKeyIndicator
    {
        return SyllabusRubricKeyIndicator::create($data);
    }

    public function update(string $id, array $data): SyllabusRubricKeyIndicator
    {
        $model = SyllabusRubricKeyIndicator::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return SyllabusRubricKeyIndicator::destroy($id);
    }
}
