<?php

namespace App\Repositories\LessonMaterial;

use App\Enums\MaterialType;
use App\Models\LessonMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class LessonMaterialRepository implements LessonMaterialRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function get(array $filters = [], array $with = []): EloquentCollection
    {
        $query = LessonMaterial::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->orderBy('order')->with($with)->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function find(string $id, array $with = []): ?LessonMaterial
    {
        return LessonMaterial::with($with)->find($id);
    }

    /**
     * @param  array<string>  $with
     */
    public function getByLesson(string $lessonId, array $with = []): EloquentCollection
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->orderBy('order')
            ->with($with)
            ->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function getByLessonAndType(string $lessonId, MaterialType $type, array $with = []): EloquentCollection
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->where('type', $type)
            ->orderBy('order')
            ->with($with)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LessonMaterial
    {
        return LessonMaterial::create([
            'lesson_id' => $data['lesson_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_url' => $data['file_url'],
            'file_path' => $data['file_path'] ?? null,
            'file_size' => $data['file_size'],
            'mime_type' => $data['mime_type'],
            'order' => $data['order'] ?? $this->getNextOrder($data['lesson_id']),
            'version' => $data['version'] ?? 1,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): LessonMaterial
    {
        $material = LessonMaterial::findOrFail($id);

        $material->update([
            'type' => $data['type'] ?? $material->type,
            'title' => $data['title'] ?? $material->title,
            'description' => $data['description'] ?? $material->description,
            'file_url' => $data['file_url'] ?? $material->file_url,
            'file_path' => $data['file_path'] ?? $material->file_path,
            'file_size' => $data['file_size'] ?? $material->file_size,
            'mime_type' => $data['mime_type'] ?? $material->mime_type,
            'version' => $data['version'] ?? $material->version,
            'is_active' => $data['is_active'] ?? $material->is_active,
        ]);

        return $material;
    }

    public function delete(string $id): int
    {
        return LessonMaterial::destroy($id);
    }

    public function getNextOrder(string $lessonId): int
    {
        return (LessonMaterial::where('lesson_id', $lessonId)->max('order') ?? 0) + 1;
    }

    /**
     * @param  array<string, int>  $orderMap  ['material_id' => order_number]
     */
    public function reorder(string $lessonId, array $orderMap): void
    {
        $materialIds = array_keys($orderMap);
        $placeholders = implode(',', array_fill(0, count($materialIds), '?'));

        // The (lesson_id, order) unique constraint is only DEFERRABLE on pgsql,
        // so a single CASE update can collide mid-statement on other drivers
        // (e.g. sqlite in CI). Shift into a negative, non-colliding range first,
        // then apply the final positive order in a second pass.
        $orderColumn = DB::getQueryGrammar()->wrap('order');

        DB::transaction(function () use ($orderMap, $lessonId, $materialIds, $placeholders, $orderColumn): void {
            $tempCaseWhen = 'CASE id';
            foreach ($orderMap as $materialId => $order) {
                $tempCaseWhen .= " WHEN '$materialId' THEN ".(-$order);
            }
            $tempCaseWhen .= ' END';

            DB::update(
                "UPDATE lesson_materials SET $orderColumn = $tempCaseWhen WHERE lesson_id = ? AND id IN ($placeholders)",
                array_merge([$lessonId], $materialIds)
            );

            $finalCaseWhen = 'CASE id';
            foreach ($orderMap as $materialId => $order) {
                $finalCaseWhen .= " WHEN '$materialId' THEN $order";
            }
            $finalCaseWhen .= ' END';

            DB::update(
                "UPDATE lesson_materials SET $orderColumn = $finalCaseWhen WHERE lesson_id = ? AND id IN ($placeholders)",
                array_merge([$lessonId], $materialIds)
            );
        });
    }

    public function findOrFail(string $id): LessonMaterial
    {
        return LessonMaterial::findOrFail($id);
    }

    public function sumActiveFileSize(): int
    {
        return (int) LessonMaterial::active()->sum('file_size');
    }

    public function getActiveForSchool(string $schoolId): EloquentCollection
    {
        return LessonMaterial::active()
            ->whereHas('lesson.module.course', fn ($q) => $q->where('school_id', $schoolId))
            ->get();
    }

    public function getMaxVersion(string $lessonId, string $title): int
    {
        return (int) LessonMaterial::where('lesson_id', $lessonId)
            ->where('title', $title)
            ->max('version');
    }

    public function getVersions(string $lessonId, string $title): EloquentCollection
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->where('title', $title)
            ->orderBy('version', 'desc')
            ->get();
    }

    public function deactivateVersions(string $lessonId, string $title): void
    {
        LessonMaterial::where('lesson_id', $lessonId)
            ->where('title', $title)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function findVersion(string $lessonId, string $title, int $version): LessonMaterial
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->where('title', $title)
            ->where('version', $version)
            ->firstOrFail();
    }

    public function countVersions(string $lessonId, string $title): int
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->where('title', $title)
            ->count();
    }

    public function findMostRecentOtherVersion(string $lessonId, string $title, int $excludingVersion): ?LessonMaterial
    {
        return LessonMaterial::where('lesson_id', $lessonId)
            ->where('title', $title)
            ->where('version', '!=', $excludingVersion)
            ->orderBy('version', 'desc')
            ->first();
    }

    public function filteredQuery(array $filters, string $sortBy = 'created_at', string $sortDirection = 'desc'): Builder
    {
        return LessonMaterial::active()
            ->when($filters['school_id'] ?? null, fn (Builder $q, string $v) => $q->whereHas(
                'lesson.module.course', fn ($qq) => $qq->where('school_id', $v)
            ))
            ->when($filters['course_id'] ?? null, fn (Builder $q, string $v) => $q->whereHas(
                'lesson.module', fn ($qq) => $qq->where('course_id', $v)
            ))
            ->when($filters['module_id'] ?? null, fn (Builder $q, string $v) => $q->whereHas(
                'lesson', fn ($qq) => $qq->where('module_id', $v)
            ))
            ->when($filters['lesson_id'] ?? null, fn (Builder $q, string $v) => $q->where('lesson_id', $v))
            ->when($filters['title'] ?? null, fn (Builder $q, string $v) => $q->whereLike('title', "%{$v}%", caseSensitive: false))
            ->with('lesson.module.course.school')
            ->orderBy($sortBy, $sortDirection);
    }
}
