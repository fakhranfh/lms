<?php

namespace App\Services;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Models\Course;
use App\Repositories\Course\CourseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CourseService
{
    public function __construct(
        private CourseRepositoryInterface $courseRepository,
        private AssessmentService $assessmentService,
    ) {}

    /**
     * Get courses with optional filters and relations.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->courseRepository->get($filters, $with);
    }

    /**
     * Get a paginated list of courses with optional filters and relations.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->courseRepository->paginate($filters, $with, $perPage);
    }

    /**
     * Find a course by ID.
     *
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Course
    {
        return $this->courseRepository->find($id, $with);
    }

    /**
     * Create a new course.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            $course = $this->courseRepository->create($data);

            $this->ensureAttendanceAssessment($course);

            return $course;
        });
    }

    /**
     * Auto-provisions the single course-wide Attendance assessment that
     * mirrors the standalone Attendance page's derived data, so the
     * Assessment page never requires a Teacher to manually create one.
     */
    public function ensureAttendanceAssessment(Course $course): void
    {
        $exists = $this->assessmentService->get([
            'course_id' => $course->id,
            'type' => AssessmentType::Attendance,
        ])->isNotEmpty();

        if ($exists) {
            return;
        }

        $this->assessmentService->create([
            'course_id' => $course->id,
            'session_id' => null,
            'type' => AssessmentType::Attendance,
            'title' => 'Attendance',
            'weight' => AssessmentType::Attendance->defaultWeight(),
            'assigned_to' => AssessmentAssignedTo::Individual,
            'start_date' => null,
            'end_date' => null,
            'status' => AssessmentStatus::Published,
        ]);
    }

    /**
     * Update a course.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Course
    {
        return $this->courseRepository->update($id, $data);
    }

    /**
     * Delete a course.
     */
    public function delete(string $id): int
    {
        return $this->courseRepository->delete($id);
    }

    /**
     * Check if slug exists for a school.
     */
    public function slugExists(string $slug, string $schoolId, ?string $excludeId = null): bool
    {
        return $this->courseRepository->slugExistsForSchool($slug, $schoolId, $excludeId);
    }

    /**
     * Publish a course.
     */
    public function publish(string $id): void
    {
        $this->courseRepository->publish($id);
    }

    /**
     * Unpublish a course.
     */
    public function unpublish(string $id): void
    {
        $this->courseRepository->unpublish($id);
    }
}
