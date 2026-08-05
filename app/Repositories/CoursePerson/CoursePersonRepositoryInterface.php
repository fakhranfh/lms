<?php

namespace App\Repositories\CoursePerson;

use App\Models\CoursePerson;
use Illuminate\Database\Eloquent\Collection;

interface CoursePersonRepositoryInterface
{
    /**
     * @return Collection<int, CoursePerson>
     */
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?CoursePerson;

    public function create(array $data): CoursePerson;

    public function update(string $id, array $data): CoursePerson;

    public function delete(string $id): int;

    /**
     * @return Collection<int, CoursePerson>
     */
    public function studentsForCourse(string $courseId): Collection;

    /**
     * @return Collection<int, CoursePerson>
     */
    public function teachersForCourse(string $courseId): Collection;
}
