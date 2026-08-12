<?php

namespace App\Repositories\CoursePerson;

use App\Enums\RoleInCourse;
use App\Models\CoursePerson;
use Illuminate\Database\Eloquent\Collection;

class CoursePersonRepository implements CoursePersonRepositoryInterface
{
    /**
     * @return Collection<int, CoursePerson>
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = CoursePerson::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?CoursePerson
    {
        return CoursePerson::with($with)->find($id);
    }

    public function create(array $data): CoursePerson
    {
        return CoursePerson::create($data);
    }

    public function update(string $id, array $data): CoursePerson
    {
        $model = CoursePerson::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return CoursePerson::destroy($id);
    }

    /**
     * @return Collection<int, CoursePerson>
     */
    public function studentsForCourse(string $courseId): Collection
    {
        return CoursePerson::where('course_id', $courseId)
            ->where('role_in_course', RoleInCourse::Student)
            ->with('user')
            ->get();
    }

    /**
     * @return Collection<int, CoursePerson>
     */
    public function teachersForCourse(string $courseId): Collection
    {
        return CoursePerson::where('course_id', $courseId)
            ->whereIn('role_in_course', [RoleInCourse::Teacher, RoleInCourse::Assistant])
            ->with('user')
            ->get();
    }

    public function isEnrolledAsStudent(string $courseId, string $userId): bool
    {
        return CoursePerson::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->where('role_in_course', RoleInCourse::Student)
            ->exists();
    }
}
