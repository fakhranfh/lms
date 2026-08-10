<?php

namespace App\Livewire\Courses;

use App\Enums\RoleName;
use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\Role;
use App\Models\Session;
use App\Services\CoursePersonService;
use App\Services\ForumService;
use App\Services\ForumThreadReadService;
use App\Services\ForumThreadService;
use App\Services\R2StorageService;
use App\Services\RichTextAttachmentCleanupService;
use App\Services\SessionService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class ForumIndex extends Component
{
    use WithFileUploads;

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

    public $pendingRichTextFile = null;

    public function insertRichTextFile(R2StorageService $r2StorageService): string
    {
        $this->validate([
            'pendingRichTextFile' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf,zip|max:10240',
        ]);

        $url = $r2StorageService->uploadPublicFile($this->pendingRichTextFile, 'forum-attachments');

        $this->pendingRichTextFile = null;

        return $url;
    }

    public function deleteRichTextAttachment(string $url, RichTextAttachmentCleanupService $richTextAttachmentCleanupService): void
    {
        $richTextAttachmentCleanupService->deleteUrl($url);
    }

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

    public function selectSession(string $sessionId): void
    {
        $this->sessionId = $sessionId;
        $this->page = 1;
        $this->resetThreadForm();
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
            'description' => HtmlSanitizer::forum($this->newThreadDescription),
        ]);

        $this->page = 1;
        $this->resetThreadForm();
        $this->dispatch('thread-created');
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

        $sessions = $sessionService->forCourse($this->course->id);

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

        $tabs = $sessions->map(fn ($session) => [
            'key' => $session->id,
            'label' => $session->title,
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
