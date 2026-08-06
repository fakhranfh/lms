<?php

namespace App\Services;

use App\Models\ForumThread;
use App\Models\ForumThreadRead;
use App\Repositories\ForumThread\ForumThreadRepositoryInterface;
use App\Repositories\ForumThreadRead\ForumThreadReadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ForumThreadReadService
{
    public function __construct(
        private ForumThreadReadRepositoryInterface $forumThreadReadRepository,
        private ForumThreadRepositoryInterface $forumThreadRepository,
    ) {}

    public function get(array $filters = [], array $with = []): EloquentCollection
    {
        return $this->forumThreadReadRepository->get($filters, $with);
    }

    public function find(string $id, array $with = []): ?ForumThreadRead
    {
        return $this->forumThreadReadRepository->find($id, $with);
    }

    public function markRead(string $threadId, string $userId): ForumThreadRead
    {
        return $this->forumThreadReadRepository->markRead($threadId, $userId);
    }

    /**
     * A thread is unread when it has never been read by the user, or when it
     * has been updated (e.g. a new comment) since the user last read it.
     *
     * @param  array<int, string>  $forumIds
     * @return array<string, int> forum_id => unread thread count
     */
    public function unreadCountsForForums(array $forumIds, string $userId): array
    {
        if ($forumIds === []) {
            return [];
        }

        /** @var Collection<int, ForumThread> $threads */
        $threads = collect($this->forumThreadRepository->forForums($forumIds)->all());

        return $this->groupUnreadCountsByForum($threads, $userId);
    }

    /**
     * @param  Collection<int, ForumThread>  $threads
     * @return array<int, string> thread ids that are unread for this user
     */
    public function unreadThreadIds(Collection $threads, string $userId): array
    {
        $readAtByThread = $this->forumThreadReadRepository->readAtByThreadForUser($threads->pluck('id')->all(), $userId);

        return $this->filterUnread($threads, $readAtByThread)->pluck('id')->all();
    }

    /**
     * @param  Collection<int, ForumThread>  $threads
     * @return array<string, int>
     */
    private function groupUnreadCountsByForum(Collection $threads, string $userId): array
    {
        $readAtByThread = $this->forumThreadReadRepository->readAtByThreadForUser($threads->pluck('id')->all(), $userId);

        return $this->filterUnread($threads, $readAtByThread)
            ->groupBy('forum_id')
            ->map(fn (Collection $forumThreads) => $forumThreads->count())
            ->all();
    }

    /**
     * @param  Collection<int, ForumThread>  $threads
     * @param  Collection<string, Carbon>  $readAtByThread
     * @return Collection<int, ForumThread>
     */
    private function filterUnread(Collection $threads, Collection $readAtByThread): Collection
    {
        return $threads->filter(function (ForumThread $thread) use ($readAtByThread) {
            $readAt = $readAtByThread[$thread->id] ?? null;

            return $readAt === null || $readAt->lt($thread->updated_at);
        });
    }
}
