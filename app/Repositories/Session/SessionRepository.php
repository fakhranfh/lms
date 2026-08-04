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

    public function forCourse(string $courseId, array $with = []): Collection
    {
        return Session::where('course_id', $courseId)->with($with)->orderBy('date_start')->get();
    }
}
