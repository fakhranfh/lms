<?php

namespace App\Repositories\Assignment;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Collection;

class AssignmentRepository implements AssignmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Assignment::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?Assignment
    {
        return Assignment::with($with)->find($id);
    }

    /**
     * @param  array<string>  $with
     */
    public function getByLesson(string $lessonId, array $with = []): Collection
    {
        return Assignment::where('lesson_id', $lessonId)
            ->with($with)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Assignment
    {
        return Assignment::create([
            'lesson_id' => $data['lesson_id'],
            'title' => $data['title'],
            'prompt_question' => $data['prompt_question'],
            'rubric' => $data['rubric'] ?? null,
            'max_score' => $data['max_score'] ?? 100.00,
            'passing_score' => $data['passing_score'] ?? null,
            'is_published' => $data['is_published'] ?? false,
            'allow_multiple_submissions' => $data['allow_multiple_submissions'] ?? false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Assignment
    {
        $assignment = Assignment::findOrFail($id);

        $assignment->update([
            'title' => $data['title'] ?? $assignment->title,
            'prompt_question' => $data['prompt_question'] ?? $assignment->prompt_question,
            'rubric' => $data['rubric'] ?? $assignment->rubric,
            'max_score' => $data['max_score'] ?? $assignment->max_score,
            'passing_score' => $data['passing_score'] ?? $assignment->passing_score,
            'is_published' => $data['is_published'] ?? $assignment->is_published,
            'allow_multiple_submissions' => $data['allow_multiple_submissions'] ?? $assignment->allow_multiple_submissions,
        ]);

        return $assignment;
    }

    public function delete(string $id): int
    {
        return Assignment::destroy($id);
    }
}
