<?php

namespace App\Repositories\Course;

use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository implements CourseRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Course::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            if ($key === 'search') {
                $query->where('title', 'like', "%{$value}%")
                    ->orWhere('description', 'like', "%{$value}%");
            } else {
                $query->where($key, $value);
            }
        }

        return $query->with($with)->get();
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
            'slug' => $data['slug'],
            'is_published' => $data['is_published'] ?? false,
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
            'slug' => $data['slug'] ?? $course->slug,
            'is_published' => $data['is_published'] ?? $course->is_published,
        ]);

        return $course;
    }

    public function delete(string $id): int
    {
        return Course::destroy($id);
    }

    public function slugExistsForSchool(string $slug, string $schoolId, ?string $excludeId = null): bool
    {
        $query = Course::where('slug', $slug)
            ->where('school_id', $schoolId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function publish(string $id): void
    {
        Course::findOrFail($id)->update(['is_published' => true]);
    }

    public function unpublish(string $id): void
    {
        Course::findOrFail($id)->update(['is_published' => false]);
    }
}
