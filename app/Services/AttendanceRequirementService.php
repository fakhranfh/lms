<?php

namespace App\Services;

use App\Models\AttendanceRequirement;
use App\Repositories\AttendanceRequirement\AttendanceRequirementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRequirementService
{
    public function __construct(
        private AttendanceRequirementRepositoryInterface $attendanceRequirementRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->attendanceRequirementRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?AttendanceRequirement
    {
        return $this->attendanceRequirementRepository->find($id, $with);
    }

    public function create(array $data): AttendanceRequirement
    {
        return $this->attendanceRequirementRepository->create($data);
    }

    public function update(string $id, array $data): AttendanceRequirement
    {
        return $this->attendanceRequirementRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->attendanceRequirementRepository->delete($id);
    }

    public function forCourse(string $courseId): Collection
    {
        return $this->attendanceRequirementRepository->forCourse($courseId);
    }
}
