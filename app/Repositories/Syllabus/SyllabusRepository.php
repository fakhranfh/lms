<?php

namespace App\Repositories\Syllabus;

use App\Models\Syllabus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SyllabusRepository implements SyllabusRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection
    {
        $query = Syllabus::query();

        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->with($with)->get();
    }

    public function find(string $id, array $with = []): ?Syllabus
    {
        return Syllabus::with($with)->find($id);
    }

    public function create(array $data): Syllabus
    {
        return Syllabus::create($data);
    }

    public function update(string $id, array $data): Syllabus
    {
        $model = Syllabus::findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): int
    {
        return Syllabus::destroy($id);
    }

    public function findByCourse(string $courseId, array $with = []): ?Syllabus
    {
        return Syllabus::with($with)->where('course_id', $courseId)->first();
    }

    /**
     * Rebuilds the syllabus_materials pivot rows for a syllabus. Not a plain
     * `sync()` because a single media item can legitimately attach to more
     * than one section, keyed by the `section` pivot column.
     *
     * @param  array<string, array<int, string>>  $selectedMaterialIdsBySection
     */
    public function replaceMaterials(string $syllabusId, array $selectedMaterialIdsBySection): void
    {
        DB::table('syllabus_materials')->where('syllabus_id', $syllabusId)->delete();

        $rows = [];
        foreach ($selectedMaterialIdsBySection as $section => $materialIds) {
            foreach (array_values($materialIds) as $order => $materialId) {
                $rows[] = [
                    'syllabus_id' => $syllabusId,
                    'section' => $section,
                    'media_library_item_id' => $materialId,
                    'order' => $order + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            DB::table('syllabus_materials')->insert($rows);
        }
    }
}
