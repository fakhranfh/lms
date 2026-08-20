<?php

namespace App\Repositories\ProctorSnapshot;

use App\Models\ProctorSnapshot;
use Illuminate\Database\Eloquent\Collection;

interface ProctorSnapshotRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ProctorSnapshot;

    public function create(array $data): ProctorSnapshot;

    public function update(string $id, array $data): ProctorSnapshot;

    public function delete(string $id): int;

    public function forSession(string $proctorSessionId): Collection;

    /**
     * Distinct event types (plus 'none' for untriggered screenshots) among
     * a session's screenshots, computed at the database level.
     *
     * @return array<int, string>
     */
    public function screenshotEventTypesForSession(string $proctorSessionId): array;

    /**
     * Filters, sorts, and paginates a session's screenshots at the
     * database level rather than loading the full set into memory.
     *
     * @return array{items: Collection<int, ProctorSnapshot>, total: int}
     */
    public function paginateScreenshotsForSession(
        string $proctorSessionId,
        ?string $eventType,
        string $sort,
        int $offset,
        int $limit,
    ): array;
}
