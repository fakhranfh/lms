<?php

namespace App\Repositories\Session;

use App\Models\Session;
use Illuminate\Database\Eloquent\Collection;

class SessionRepository implements SessionRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Session::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?Session
    {
        return Session::with($with)->find($id);
    }

    public function create(array $data): Session
    {
        return Session::create($data);
    }

    public function update(string $id, array $data): Session
    {
        $session = Session::findOrFail($id);
        $session->update($data);

        return $session;
    }

    public function delete(string $id): int
    {
        return Session::destroy($id);
    }

    public function deleteMany(array $ids): int
    {
        return Session::destroy($ids);
    }

    public function deleteForCourse(string $courseId): int
    {
        return Session::where('course_id', $courseId)->delete();
    }

    public function nextOrder(string $courseId): int
    {
        return (int) Session::where('course_id', $courseId)->max('order') + 1;
    }

    public function reorder(string $courseId, array $orderedIds): void
    {
        // Two passes avoid unique(course_id, order) collisions while shifting rows;
        // `order` is unsigned, so the temp pass uses a high offset instead of negatives.
        foreach ($orderedIds as $index => $id) {
            Session::where('id', $id)->where('course_id', $courseId)->update(['order' => 1_000_000 + $index]);
        }

        foreach ($orderedIds as $index => $id) {
            Session::where('id', $id)->where('course_id', $courseId)->update(['order' => $index + 1]);
        }
    }

    public function forCourse(string $courseId, array $with = []): Collection
    {
        return Session::where('course_id', $courseId)->with($with)->orderBy('order')->get();
    }
}
