<?php

namespace App\Repositories\GradebookGradeScale;

use App\Models\GradebookGradeScale;
use Illuminate\Database\Eloquent\Collection;

interface GradebookGradeScaleRepositoryInterface
{
    /**
     * @return Collection<int, GradebookGradeScale>
     */
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?GradebookGradeScale;

    public function create(array $data): GradebookGradeScale;

    public function update(string $id, array $data): GradebookGradeScale;

    public function delete(string $id): int;

    /**
     * @return Collection<int, GradebookGradeScale>
     */
    public function forCourseOrDefault(?string $courseId): Collection;
}
