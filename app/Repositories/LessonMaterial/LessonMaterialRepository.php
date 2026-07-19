<?php

namespace App\Repositories\LessonMaterial;

use App\Enums\MaterialType;
use App\Models\LessonMaterial;
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
        // Build CASE statement for single query reorder
        // Unique constraint is DEFERRABLE, so intermediate violations are OK
        $caseWhen = 'CASE id';
        foreach ($orderMap as $materialId => $order) {
            $caseWhen .= " WHEN '$materialId' THEN $order";
        }
        $caseWhen .= ' END';

        $materialIds = array_keys($orderMap);
        $placeholders = implode(',', array_fill(0, count($materialIds), '?'));

        // Single atomic query - constraint validation deferred to transaction end
        DB::update(
            "UPDATE lesson_materials SET \"order\" = $caseWhen WHERE lesson_id = ? AND id IN ($placeholders)",
            array_merge([$lessonId], $materialIds)
        );
    }
}
