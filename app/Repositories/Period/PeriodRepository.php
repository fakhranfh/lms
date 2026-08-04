<?php

namespace App\Repositories\Period;

use App\Models\Period;
use Illuminate\Database\Eloquent\Collection;

class PeriodRepository implements PeriodRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Period::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?Period
    {
        return Period::with($with)->find($id);
    }

    public function create(array $data): Period
    {
        return Period::create($data);
    }

    public function update(string $id, array $data): Period
    {
        $model = Period::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Period::destroy($id);
    }
}
