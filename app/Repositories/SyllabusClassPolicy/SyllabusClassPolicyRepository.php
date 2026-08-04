<?php

namespace App\Repositories\SyllabusClassPolicy;

use App\Models\SyllabusClassPolicy;
use Illuminate\Database\Eloquent\Collection;

class SyllabusClassPolicyRepository implements SyllabusClassPolicyRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = SyllabusClassPolicy::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->get();
    }

    public function find(string $id, array $with = []): ?SyllabusClassPolicy
    {
        return SyllabusClassPolicy::with($with)->find($id);
    }

    public function create(array $data): SyllabusClassPolicy
    {
        return SyllabusClassPolicy::create($data);
    }

    public function update(string $id, array $data): SyllabusClassPolicy
    {
        $model = SyllabusClassPolicy::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return SyllabusClassPolicy::destroy($id);
    }
}
