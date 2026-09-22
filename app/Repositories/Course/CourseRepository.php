<?php

namespace App\Repositories\Course;

use App\Enums\CourseMembershipStatus;
use App\Enums\RoleInCourse;
use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository implements CourseRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        return $this->applyFilters(Course::query(), $filters)->with($with)->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginate(array $filters = [], array $with = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->applyFilters(Course::query(), $filters)->with($with)->latest()->paginate($perPage);
    }

    /**
     * @param  Builder<Course>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Course>
     */
    private function applyFilters($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            if ($key === 'search') {
                $query->where(function ($subQuery) use ($value) {
                    $subQuery->whereLike('title', "%{$value}%", caseSensitive: false)
                        ->orWhereLike('description', "%{$value}%", caseSensitive: false);
                });
            } elseif ($key === 'enrolled_user_id') {
                $query->whereHas('people', function ($subQuery) use ($value) {
                    $subQuery->where('user_id', $value)
                        ->where('status', CourseMembershipStatus::Active);
                });
            } elseif ($key === 'teaching_user_id') {
                $query->whereHas('people', function ($subQuery) use ($value) {
                    $subQuery->where('user_id', $value)
                        ->whereIn('role_in_course', [RoleInCourse::Teacher, RoleInCourse::Assistant])
                        ->where('status', CourseMembershipStatus::Active);
                });
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Course
    {
        return Course::with($with)->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Course
    {
        return Course::create([
            'school_id' => $data['school_id'],
            'created_by' => $data['created_by'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Course
    {
        $course = Course::findOrFail($id);

        $course->update([
            'title' => $data['title'] ?? $course->title,
            'description' => $data['description'] ?? $course->description,
        ]);

        return $course;
    }

    public function delete(string $id): int
    {
        return Course::destroy($id);
    }

    public function bulkDelete(array $ids): int
    {
        return Course::destroy($ids);
    }
}
