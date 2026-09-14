<?php

namespace App\Services;

use App\Models\Attendance;
use App\Repositories\Attendance\AttendanceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    public function __construct(
        private AttendanceRepositoryInterface $attendanceRepository
    ) {}

    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->attendanceRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?Attendance
    {
        return $this->attendanceRepository->find($id, $with);
    }

    public function create(array $data): Attendance
    {
        return $this->attendanceRepository->create($data);
    }

    public function update(string $id, array $data): Attendance
    {
        return $this->attendanceRepository->update($id, $data);
    }

    public function delete(string $id): int
    {
        return $this->attendanceRepository->delete($id);
    }

    public function findBySessionAndUser(string $sessionId, string $userId): ?Attendance
    {
        return $this->attendanceRepository->findBySessionAndUser($sessionId, $userId);
    }

    public function forUser(string $userId): Collection
    {
        return $this->attendanceRepository->forUser($userId);
    }

    public function deleteForCourse(string $courseId): int
    {
        return $this->attendanceRepository->deleteForCourse($courseId);
    }
}
