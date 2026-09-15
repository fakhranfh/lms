<?php

namespace App\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Enums\RoleName;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\Role;
use App\Models\Session;
use App\Services\CoursePersonService;
use App\Services\ForumService;
use App\Services\ForumThreadReadService;
use App\Services\ForumThreadService;
use App\Services\SessionService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class ForumIndex extends Component
{
    use WithRichTextEditor;

    public Course $course;

    public bool $isStudent = false;

    #[Url(as: 'session')]
    public ?string $sessionId = null;

    /**
     * Forum is queried lazily via wire:init (loadForum), so the initial
     * page render is a cheap skeleton instead of blocking on the query.
     */
    public bool $forumLoaded = false;

    public ?string $currentForumId = null;

    public int $perPage = 10;

    public int $page = 1;

    public string $newThreadTitle = '';

    public string $newThreadDescription = '';

    public int $generateThreadCount = 5;

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('forum.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function loadForum(): void
    {
        $this->forumLoaded = true;
    }

    public function selectSession(string $sessionId, SessionService $sessionService): void
    {
        $this->sessionId = $sessionId;
        $this->page = 1;
        $this->resetThreadForm();

        $session = $sessionService->find($sessionId);

        $this->dispatch('rich-text-disabled-changed', id: 'new-thread', disabled: ! ($session?->isOngoing() ?? false));
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function createThread(ForumThreadService $forumThreadService, ForumService $forumService): void
    {
        abort_unless(auth()->user()->can('forum.create'), 403);
        abort_unless($this->currentForumId !== null, 404);

        $forum = $forumService->find($this->currentForumId, ['session']);

        abort_unless($forum !== null && $forum->session->isOngoing(), 403);

        $this->validate([
            'newThreadTitle' => 'required|string|max:255',
            'newThreadDescription' => 'nullable|string',
        ]);

        $forumThreadService->create([
            'forum_id' => $this->currentForumId,
            'user_id' => auth()->id(),
            'title' => $this->newThreadTitle,
            'description' => HtmlSanitizer::forum($this->promoteRichTextAttachments($this->newThreadDescription)),
        ]);

        $this->page = 1;
        $this->resetThreadForm();
        $this->dispatch('thread-created');
    }

    /**
     * Dev-only helper to bulk-create fake threads for the current session's
     * forum, so a developer can quickly populate data for testing pagination,
     * moderation, or the forum monitoring page without posting by hand.
     */
    public function generateThreads(ForumThreadService $forumThreadService): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        abort_unless(auth()->user()->can('forum.create'), 403);
        abort_unless($this->currentForumId !== null, 404);

        $count = max(1, min(50, $this->generateThreadCount));
        $topics = $this->fakeThreadTopics();

        for ($i = 0; $i < $count; $i++) {
            $topic = $topics[$i % count($topics)];

            $forumThreadService->create([
                'forum_id' => $this->currentForumId,
                'user_id' => auth()->id(),
                'title' => $topic['title'].' #'.($i + 1),
                'description' => HtmlSanitizer::forum($topic['description']),
            ]);
        }

        $this->page = 1;
        $this->dispatch('thread-created');
    }

    /**
     * Realistic-sounding thread title/description pairs for dev-only fake
     * data generation — deliberately not Lorem Ipsum so generated threads
     * are easy to skim while testing pagination, moderation, or monitoring.
     *
     * @return array<int, array{title: string, description: string}>
     */
    private function fakeThreadTopics(): array
    {
        return [
            ['title' => 'Pertanyaan tentang materi minggu ini', 'description' => 'Ada bagian materi yang belum saya pahami sepenuhnya, apakah ada yang bisa menjelaskan ulang dengan contoh sederhana?'],
            ['title' => 'Diskusi tugas kelompok', 'description' => 'Mari kita samakan pembagian tugas kelompok di sini supaya tidak ada yang tumpang tindih sebelum deadline.'],
            ['title' => 'Kesulitan memahami konsep dasar', 'description' => 'Saya masih bingung dengan konsep dasar yang dijelaskan di sesi ini, mohon bantuan teman-teman untuk berdiskusi.'],
            ['title' => 'Berbagi catatan belajar', 'description' => 'Saya sudah merangkum poin-poin penting dari sesi ini, silakan cek dan tambahkan kalau ada yang terlewat.'],
            ['title' => 'Tanya jawab sebelum ujian', 'description' => 'Sebelum ujian minggu depan, ada yang mau tanya-jawab soal materi yang sering keluar di latihan soal?'],
            ['title' => 'Review sesi sebelumnya', 'description' => 'Menurut kalian bagian mana dari sesi sebelumnya yang paling sulit dipahami? Yuk kita bahas bersama.'],
            ['title' => 'Rekomendasi sumber belajar tambahan', 'description' => 'Ada rekomendasi video atau artikel tambahan yang membantu memahami topik ini lebih dalam?'],
            ['title' => 'Klarifikasi deadline tugas', 'description' => 'Mohon konfirmasi apakah deadline tugas untuk sesi ini tetap sesuai jadwal atau ada perubahan.'],
            ['title' => 'Sharing pengalaman praktik', 'description' => 'Saya baru saja mencoba menerapkan materi ini langsung, mau berbagi pengalaman sekaligus tanya pendapat kalian.'],
            ['title' => 'Diskusi studi kasus', 'description' => 'Bagaimana pendapat kalian tentang studi kasus yang diberikan di sesi ini? Ada pendekatan lain yang lebih efektif?'],
        ];
    }

    public function confirmDeleteThread(string $threadId, ForumThreadService $forumThreadService): void
    {
        $thread = $forumThreadService->find($threadId);

        if (! $thread) {
            return;
        }

        abort_unless($thread->user_id === auth()->id() || auth()->user()->can('forum.moderate'), 403);

        $forumThreadService->delete($threadId);
    }

    /**
     * Selection is tracked client-side (Alpine) so checking boxes or
     * "select all on this page" never round-trips to the server — only the
     * final delete call does, passing the selected ids as a plain array.
     *
     * @param  array<int, string>  $threadIds
     */
    public function bulkDeleteThreads(array $threadIds, ForumThreadService $forumThreadService): void
    {
        abort_unless(auth()->user()->can('forum.moderate'), 403);

        foreach ($threadIds as $threadId) {
            $thread = $forumThreadService->find($threadId);

            if ($thread && $thread->forum_id === $this->currentForumId) {
                $forumThreadService->delete($threadId);
            }
        }

        $this->page = 1;
    }

    private function resetThreadForm(): void
    {
        $this->newThreadTitle = '';
        $this->newThreadDescription = '';
        $this->resetErrorBag(['newThreadTitle', 'newThreadDescription']);
    }

    public function render(
        ForumService $forumService,
        ForumThreadService $forumThreadService,
        ForumThreadReadService $forumThreadReadService,
        SessionService $sessionService,
        CoursePersonService $coursePersonService
    ) {
        if (! $this->forumLoaded) {
            return view('livewire.courses.forum-index-placeholder', [
                'course' => $this->course,
                'isStudent' => $this->isStudent,
                'courseTabs' => CourseTabs::build($this->course, 'forum'),
                'teacher' => $this->isStudent
                    ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                    : null,
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $sessions = $sessionService->forCourse($this->course->id)
            ->filter(fn (Session $session) => $session->delivery_mode === DeliveryMode::Online)
            ->values();

        if ($sessions->isEmpty()) {
            return view('livewire.courses.forum-index-empty', [
                'course' => $this->course,
                'courseTabs' => CourseTabs::build($this->course, 'forum'),
                'teacher' => $this->isStudent
                    ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                    : null,
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        if ($this->sessionId === null || ! $sessions->contains('id', $this->sessionId)) {
            $this->sessionId = $sessions->first()->id;
        }

        $sessionForums = $sessions->mapWithKeys(
            fn ($session) => [$session->id => $forumService->findOrCreateForSession($session->id, $this->course->id)]
        );

        $forum = $sessionForums->get($this->sessionId);

        $this->currentForumId = $forum->id;

        $allForumIds = $sessionForums->pluck('id')->all();
        $unreadCounts = $forumThreadReadService->unreadCountsForForums($allForumIds, auth()->id());

        $activeSession = $sessions->firstWhere('id', $this->sessionId);

        $threads = $forumThreadService->paginateForForum($forum->id, $this->perPage, $this->page, ['user', 'user.roles']);
        $totals = $forumThreadService->totalPostsForForum($forum->id);
        $unreadThreadIds = $forumThreadReadService->unreadThreadIds(collect($threads->items()), auth()->id());

        return view('livewire.courses.forum-index', [
            'course' => $this->course,
            'sessionTabs' => $this->buildSessionTabs($sessions, $sessionForums, $unreadCounts),
            'activeTitle' => $activeSession->title,
            'activeSession' => $activeSession,
            'threadRows' => $this->buildThreadRows($threads, $unreadThreadIds),
            'pagination' => [
                'total' => $threads->total(),
                'currentPage' => $threads->currentPage(),
                'lastPage' => $threads->lastPage(),
                'onFirstPage' => $threads->onFirstPage(),
                'hasMorePages' => $threads->hasMorePages(),
            ],
            'totalThreads' => $totals['threads'],
            'totalComments' => $totals['comments'],
            'isStudent' => $this->isStudent,
            'canCreate' => auth()->user()->can('forum.create'),
            'forumWindowOpen' => $activeSession->isOngoing(),
            'canModerate' => auth()->user()->can('forum.moderate'),
            'currentPageThreadIds' => collect($threads->items())->pluck('id')->all(),
            'courseTabs' => CourseTabs::build($this->course, 'forum'),
            'teacher' => $this->isStudent
                ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                : null,
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Session>  $sessions
     * @param  Collection<string, Forum>  $sessionForums
     * @param  array<string, int>  $unreadCounts
     * @return array{visible: array<int, array<string, mixed>>, overflow: array<int, array<string, mixed>>}
     */
    private function buildSessionTabs($sessions, $sessionForums, array $unreadCounts): array
    {
        $maxVisibleTabs = 7;

        $tabs = $sessions->values()->map(fn ($session, $index) => [
            'key' => $session->id,
            'label' => 'Session '.($index + 1),
            'unreadCount' => $unreadCounts[$sessionForums->get($session->id)?->id] ?? 0,
            'active' => $this->sessionId === $session->id,
        ]);

        return [
            'visible' => $tabs->take($maxVisibleTabs)->values()->all(),
            'overflow' => $tabs->slice($maxVisibleTabs)->values()->all(),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, ForumThread>  $threads
     * @param  array<int, string>  $unreadThreadIds
     * @return array<int, array<string, mixed>>
     */
    private function buildThreadRows(LengthAwarePaginator $threads, array $unreadThreadIds): array
    {
        $rows = [];

        /** @var ForumThread $thread */
        foreach ($threads->items() as $thread) {
            /** @var Role|null $role */
            $role = $thread->user->roles->first();

            $rows[] = [
                'id' => $thread->id,
                'title' => $thread->title,
                'commentsCount' => $thread->comments_count,
                'createdAtLabel' => $thread->created_at_display->format('d M Y, H:i'),
                'isUnread' => in_array($thread->id, $unreadThreadIds, true),
                'canDelete' => $thread->user_id === auth()->id() || auth()->user()->can('forum.moderate'),
                'userName' => $thread->user->name,
                'userInitial' => strtoupper(substr($thread->user->name ?: 'U', 0, 1)),
                'userAvatarUrl' => $thread->user->profile_photo_path,
                'roleLabel' => $role?->name,
            ];
        }

        return $rows;
    }
}
