<?php

namespace App\Services;

use App\Models\CourseAttendanceSetting;
use App\Repositories\CourseAttendanceSetting\CourseAttendanceSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CourseAttendanceSettingService
{
    public function __construct(
        private CourseAttendanceSettingRepositoryInterface $courseAttendanceSettingRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->courseAttendanceSettingRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?CourseAttendanceSetting
    {
        return $this->courseAttendanceSettingRepository->find($id, $with);
    }

    public function create(array $data): CourseAttendanceSetting
    {
        return $this->courseAttendanceSettingRepository->create($data);
    }

    public function update(string $id, array $data): CourseAttendanceSetting
    {
        return $this->courseAttendanceSettingRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->courseAttendanceSettingRepository->delete($id);
    }

    public function findByCourse(string $courseId): ?CourseAttendanceSetting
    {
        return $this->courseAttendanceSettingRepository->findByCourse($courseId);
    }
}
