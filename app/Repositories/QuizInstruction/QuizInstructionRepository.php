<?php

namespace App\Repositories\QuizInstruction;

use App\Models\QuizInstruction;
use Illuminate\Database\Eloquent\Collection;

class QuizInstructionRepository implements QuizInstructionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = QuizInstruction::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?QuizInstruction
    {
        return QuizInstruction::with($with)->find($id);
    }

    public function create(array $data): QuizInstruction
    {
        return QuizInstruction::create($data);
    }

    public function update(string $id, array $data): QuizInstruction
    {
        $model = QuizInstruction::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return QuizInstruction::destroy($id);
    }

    public function current(): ?QuizInstruction
    {
        return QuizInstruction::latest('updated_at')->first();
    }
}
