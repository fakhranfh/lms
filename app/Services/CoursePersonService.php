<?php

namespace App\Services;

use App\Models\CoursePerson;
use App\Repositories\CoursePerson\CoursePersonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CoursePersonService
{
    public function __construct(
        private CoursePersonRepositoryInterface $coursePersonRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->coursePersonRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?CoursePerson
    {
        return $this->coursePersonRepository->find($id, $with);
    }

    public function create(array $data): CoursePerson
    {
        return $this->coursePersonRepository->create($data);
    }

    public function update(string $id, array $data): CoursePerson
    {
        return $this->coursePersonRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->coursePersonRepository->delete($id);
    }

    /**
     * @return Collection<int, CoursePerson>
     */
    public function studentsForCourse(string $courseId): Collection
    {
        return $this->coursePersonRepository->studentsForCourse($courseId);
    }

    /**
     * @return Collection<int, CoursePerson>
     */
    public function teachersForCourse(string $courseId): Collection
    {
        return $this->coursePersonRepository->teachersForCourse($courseId);
    }
}
