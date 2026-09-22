<?php

namespace App\Services;

use App\Enums\AssessmentAssignedTo;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
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
        private CoursePersonService $coursePersonService,
    ) {}

    /**
     * Get courses with optional filters and relations.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     * @return Collection<int, Course>
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
            $this->ensureForumDiscussionAssessment($course);

            if (! empty($data['created_by'])) {
                $this->coursePersonService->enroll($course->id, $data['created_by'], [
                    'role_in_course' => RoleInCourse::Teacher,
                    'enrolled_at' => now(),
                    'status' => CourseMembershipStatus::Active,
                ]);
            }

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
     * Auto-provisions the single course-wide Forum Discussion assessment
     * that mirrors forum participation data, so the Assessment page never
     * requires a Teacher to manually create one.
     */
    public function ensureForumDiscussionAssessment(Course $course): void
    {
        $exists = $this->assessmentService->get([
            'course_id' => $course->id,
            'type' => AssessmentType::ForumDiscussion,
        ])->isNotEmpty();

        if ($exists) {
            return;
        }

        $this->assessmentService->create([
            'course_id' => $course->id,
            'session_id' => null,
            'type' => AssessmentType::ForumDiscussion,
            'title' => 'Forum Discussion',
            'weight' => AssessmentType::ForumDiscussion->defaultWeight(),
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
     * Delete multiple courses.
     *
     * @param  array<int, string>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        return $this->courseRepository->bulkDelete($ids);
    }
}
