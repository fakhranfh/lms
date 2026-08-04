<?php

namespace App\Repositories\MediaLibrary;

use App\Models\MediaLibraryItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface MediaLibraryRepositoryInterface
{
    /**
     * Create a new media library item.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MediaLibraryItem;

    /**
     * Find a media library item by ID or throw.
     */
    public function findOrFail(string $id): MediaLibraryItem;

    /**
     * Update a media library item.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): MediaLibraryItem;

    /**
     * Delete a media library item.
     */
    public function delete(string $id): int;

    /**
     * Query builder for a school's media items matching the given filters, for pagination/listing.
     */
    public function filteredQuery(string $schoolId, ?string $type = null, ?string $search = null): Builder;

    /**
     * Sum of file_size across all media items (optionally scoped to a school).
     */
    public function sumFileSize(?string $schoolId = null): int;

    /**
     * All media items for a school, for storage breakdown reporting.
     *
     * @return Collection<int, MediaLibraryItem>
     */
    public function getForSchool(string $schoolId): Collection;
}
