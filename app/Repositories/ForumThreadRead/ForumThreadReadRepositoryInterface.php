<?php

namespace App\Repositories\ForumThreadRead;

use App\Models\ForumThreadRead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;

interface ForumThreadReadRepositoryInterface
{
    public function get(array $filters = [], array $with = []): Collection;

    public function find(string $id, array $with = []): ?ForumThreadRead;

    public function create(array $data): ForumThreadRead;

    public function update(string $id, array $data): ForumThreadRead;

    public function delete(string $id): int;

    public function markRead(string $threadId, string $userId): ForumThreadRead;

    /**
     * @param  array<int, string>  $threadIds
     * @return BaseCollection<string, Carbon>
     */
    public function readAtByThreadForUser(array $threadIds, string $userId): BaseCollection;
}
