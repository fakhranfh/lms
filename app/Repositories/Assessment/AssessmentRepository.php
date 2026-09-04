<?php

namespace App\Repositories\Assessment;

use App\Models\Assessment;
use Illuminate\Database\Eloquent\Collection;

class AssessmentRepository implements AssessmentRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Assessment::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->orderBy('order')->orderBy('created_at')->get();
    }

    public function find(string $id, array $with = []): ?Assessment
    {
        return Assessment::with($with)->find($id);
    }

    public function create(array $data): Assessment
    {
        if (! isset($data['order'])) {
            $data['order'] = Assessment::where('course_id', $data['course_id'])
                ->where('type', $data['type'])
                ->max('order') + 1;
        }

        return Assessment::create($data);
    }

    public function update(string $id, array $data): Assessment
    {
        $model = Assessment::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Assessment::destroy($id);
    }

    public function forCourse(string $courseId): Collection
    {
        return Assessment::where('course_id', $courseId)->orderBy('order')->orderBy('created_at')->get();
    }

    public function reorder(string $courseId, string $type, array $orderedIds): void
    {
        // Two passes avoid order collisions while shifting rows; `order` is
        // unsigned, so the temp pass uses a high offset instead of negatives.
        foreach ($orderedIds as $index => $id) {
            Assessment::where('id', $id)->where('course_id', $courseId)->where('type', $type)->update(['order' => 1_000_000 + $index]);
        }

        foreach ($orderedIds as $index => $id) {
            Assessment::where('id', $id)->where('course_id', $courseId)->where('type', $type)->update(['order' => $index + 1]);
        }
    }
}
