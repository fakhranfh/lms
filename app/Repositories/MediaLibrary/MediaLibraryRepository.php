<?php

namespace App\Repositories\MediaLibrary;

use App\Models\MediaLibraryItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MediaLibraryRepository implements MediaLibraryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MediaLibraryItem
    {
        return MediaLibraryItem::create([
            'school_id' => $data['school_id'],
            'uploaded_by' => $data['uploaded_by'] ?? null,
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $data['file_path'],
            'file_size' => $data['file_size'],
            'mime_type' => $data['mime_type'],
        ]);
    }

    public function findOrFail(string $id): MediaLibraryItem
    {
        return MediaLibraryItem::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): MediaLibraryItem
    {
        $item = MediaLibraryItem::findOrFail($id);

        $item->update([
            'title' => $data['title'] ?? $item->title,
            'description' => $data['description'] ?? $item->description,
        ]);

        return $item;
    }

    public function delete(string $id): int
    {
        return MediaLibraryItem::destroy($id);
    }

    public function filteredQuery(string $schoolId, ?string $type = null, ?string $search = null): Builder
    {
        return MediaLibraryItem::where('school_id', $schoolId)
            ->when($type, fn (Builder $q, string $v) => $q->where('type', $v))
            ->when($search, fn (Builder $q, string $v) => $q->whereLike('title', "%{$v}%", caseSensitive: false))
            ->with('uploader')
            ->orderBy('created_at', 'desc');
    }

    public function sumFileSize(?string $schoolId = null): int
    {
        return (int) MediaLibraryItem::when($schoolId, fn (Builder $q, string $id) => $q->where('school_id', $id))
            ->sum('file_size');
    }

    public function getForSchool(string $schoolId): Collection
    {
        return MediaLibraryItem::where('school_id', $schoolId)->get();
    }
}
