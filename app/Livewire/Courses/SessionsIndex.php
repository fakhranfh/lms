<?php

namespace App\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Enums\MaterialType;
use App\Enums\RoleName;
use App\Livewire\Concerns\WithRichTextEditor;
use App\Models\Course;
use App\Models\ForumThread;
use App\Models\MediaLibraryItem;
use App\Models\Role;
use App\Models\Session;
use App\Services\CoursePersonService;
use App\Services\ForumService;
use App\Services\ForumThreadService;
use App\Services\SessionMaterialCompletionService;
use App\Services\SessionProgressService;
use App\Services\SessionService;
use App\Services\VideoConferenceParticipationService;
use App\Services\VideoConferenceService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SessionsIndex extends Component
{
    use WithRichTextEditor;

    public Course $course;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $isStudent = false;

    /** @var array<string, bool> */
    public array $expandedSessions = [];

    public ?string $activeSessionId = null;

    public string $activeCategory = 'material';

    public ?string $activeMaterialId = null;

    public ?string $activeForumId = null;

    public string $newThreadTitle = '';

    public string $newThreadDescription = '';

    public int $forumPerPage = 5;

    public int $forumPage = 1;

    /**
     * Sessions are queried lazily via wire:init (loadSessions), so the initial
     * page render is a cheap skeleton instead of blocking on the query.
     */
    public bool $sessionsLoaded = false;

    public function mount(CurrentSchool $currentSchool, Course $course): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('sessions.view') && $course->school_id === $schoolId, 403);

        $this->course = $course;
        $this->isStudent = auth()->user()->hasRole(RoleName::Student);
    }

    public function loadSessions(): void
    {
        $this->sessionsLoaded = true;
    }

    public function toggleSession(string $sessionId): void
    {
        $this->expandedSessions[$sessionId] = ! ($this->expandedSessions[$sessionId] ?? false);
    }

    public function selectSession(string $sessionId): void
    {
        $this->activeSessionId = $sessionId;
        $this->activeCategory = 'material';
        $this->activeMaterialId = null;
        $this->forumPage = 1;
    }

    public function updatedForumPerPage(): void
    {
        $this->forumPage = 1;
    }

    public function gotoForumPage(int $page): void
    {
        $this->forumPage = max(1, $page);
    }

    /**
     * No-op action so opening the Forum chip triggers a Livewire round trip,
     * letting wire:loading show the forum skeleton on click.
     */
    public function viewForumTab(): void
    {
        //
    }

    /**
     * Marks a material as completed once viewed. One-way: a material that is
     * already completed cannot be marked incomplete again.
     */
    public function markMaterialCompleted(string $mediaLibraryItemId, SessionMaterialCompletionService $completionService): void
    {
        abort_unless($this->activeSessionId !== null, 404);

        $completionService->toggle($this->activeSessionId, $mediaLibraryItemId, auth()->id(), true);
    }

    /**
     * Marks a video conference as opened once its link is clicked. One-way,
     * like material completion, and idempotent (won't duplicate the record).
     */
    public function markVideoConferenceOpened(string $videoConferenceId, VideoConferenceParticipationService $participationService, VideoConferenceService $videoConferenceService): void
    {
        $videoConference = $videoConferenceService->find($videoConferenceId, ['session']);

        abort_unless($videoConference !== null && $videoConference->session->isOngoing(), 403);

        $alreadyOpened = $participationService->get([
            'video_conference_id' => $videoConferenceId,
            'user_id' => auth()->id(),
        ])->isNotEmpty();

        if ($alreadyOpened) {
            return;
        }

        $participationService->create([
            'video_conference_id' => $videoConferenceId,
            'user_id' => auth()->id(),
            'joined_at' => now(),
        ]);
    }

    public function createThread(ForumThreadService $forumThreadService, ForumService $forumService): void
    {
        abort_unless(auth()->user()->can('forum.create'), 403);
        abort_unless($this->activeForumId !== null, 404);

        $forum = $forumService->find($this->activeForumId, ['session']);

        abort_unless($forum !== null && $forum->session->isOngoing(), 403);

        $this->validate([
            'newThreadTitle' => 'required|string|max:255',
            'newThreadDescription' => 'nullable|string',
        ]);

        $forumThreadService->create([
            'forum_id' => $this->activeForumId,
            'user_id' => auth()->id(),
            'title' => $this->newThreadTitle,
            'description' => HtmlSanitizer::forum($this->newThreadDescription),
        ]);

        $this->forumPage = 1;
        $this->resetThreadForm();
        $this->dispatch('thread-created');
    }

    private function resetThreadForm(): void
    {
        $this->newThreadTitle = '';
        $this->newThreadDescription = '';
        $this->resetErrorBag(['newThreadTitle', 'newThreadDescription']);
    }

    public function confirmDelete(string $sessionId, SessionService $sessionService): void
    {
        abort_unless(auth()->user()->can('sessions.delete'), 403);

        $session = $sessionService->find($sessionId);

        if (! $session || $session->course_id !== $this->course->id) {
            $this->errorMessage = __('Session not found.');

            return;
        }

        $sessionService->delete($sessionId);
        $this->successMessage = __('Session deleted successfully.');
    }

    public function getMaterialIcon(MaterialType $type): string
    {
        return match ($type) {
            MaterialType::Video => '🎥',
            MaterialType::PDF => '📄',
            MaterialType::Document => '📝',
            MaterialType::Audio => '🎵',
            MaterialType::Presentation => '📊',
            MaterialType::Image => '🖼️',
            MaterialType::Interactive => '🎮',
            MaterialType::Markdown => '📄',
        };
    }

    /**
     * @return array{id: string, title: string, type: string, icon: string, isImage: bool, url: string|null}
     */
    public function toPreviewPayload(MediaLibraryItem $material): array
    {
        return [
            'id' => (string) $material->id,
            'title' => $material->title,
            'type' => $material->type->value,
            'icon' => $this->getMaterialIcon($material->type),
            'isImage' => $material->type->value === 'Image',
            'url' => $material->file_url,
        ];
    }

    public function render(SessionService $sessionService, SessionMaterialCompletionService $completionService, VideoConferenceParticipationService $participationService, CoursePersonService $coursePersonService, ForumThreadService $forumThreadService, SessionProgressService $sessionProgressService)
    {
        if (! $this->sessionsLoaded) {
            return view('livewire.courses.sessions-index-placeholder', [
                'course' => $this->course,
                'isStudent' => $this->isStudent,
                'courseTabs' => CourseTabs::build($this->course, 'session'),
                'teacher' => $this->isStudent
                    ? $coursePersonService->teachersForCourse($this->course->id)->first()?->user
                    : null,
            ])
                ->extends('layouts.app', ['topbarTitle' => $this->course->title])
                ->section('app-content');
        }

        $with = ['subtopics', 'materials', 'videoConferences'];

        if ($this->isStudent) {
            $with[] = 'assessments';
            $with[] = 'forums';
        }

        $sessions = $sessionService->forCourse($this->course->id, $with);

        $viewData = [
            'sessions' => $sessions,
            'courseTabs' => CourseTabs::build($this->course, 'session'),
            'teacher' => null,
        ];

        if ($this->isStudent) {
            $viewData = array_merge($viewData, $this->buildStudentViewData($sessions, $completionService, $participationService, $forumThreadService, $sessionProgressService));
            $viewData['teacher'] = $coursePersonService->teachersForCourse($this->course->id)->first()?->user;
        }

        return view($this->isStudent ? 'livewire.courses.sessions-index-student' : 'livewire.courses.sessions-index', $viewData)
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }

    /**
     * @param  Collection<int, Session>  $sessions
     * @return array<string, mixed>
     */
    private function buildStudentViewData(Collection $sessions, SessionMaterialCompletionService $completionService, VideoConferenceParticipationService $participationService, ForumThreadService $forumThreadService, SessionProgressService $sessionProgressService): array
    {
        $activeSession = $sessions->firstWhere('id', $this->activeSessionId) ?? $sessions->first();

        if (! $activeSession) {
            return [
                'activeSession' => null,
                'activeCategory' => $this->activeCategory,
                'completedMaterialIds' => collect(),
                'progressPercent' => 0,
                'chips' => [],
                'activeChipKey' => null,
                'activeItem' => null,
                'materialPayloads' => [],
                'openedVideoConferenceIds' => collect(),
                'showVideoConferences' => false,
                'videoConferenceWindowOpen' => false,
                'forumWindowOpen' => false,
                'forumTotalPosts' => 0,
                'forumMyPostsCount' => 0,
                'forumRequiredPosts' => 0,
                'forumThreadPreviews' => [],
                'canCreateForumThread' => auth()->user()->can('forum.create'),
                'forumPagination' => null,
            ];
        }

        $this->activeSessionId = $activeSession->id;

        $requiredForumPosts = $activeSession->required_forum_posts;

        $completedMaterialIds = $completionService->completedMaterialIds($activeSession->id, auth()->id());

        $forum = $activeSession->forums->first();
        $forumMyPostsCount = $forum ? $forumThreadService->myPostsCountForForum($forum->id, auth()->id()) : 0;
        $forumCompleted = $forum && $forumMyPostsCount >= $requiredForumPosts;

        $totalMaterials = $activeSession->materials->count();
        $completedMaterialsCount = $completedMaterialIds->intersect($activeSession->materials->pluck('id'))->count();
        $forumProgressFraction = match (true) {
            ! $forum => 0,
            $requiredForumPosts <= 0 => 1,
            default => min($forumMyPostsCount, $requiredForumPosts) / $requiredForumPosts,
        };

        $totalProgressUnits = $totalMaterials + ($forum ? 1 : 0);
        $completedProgressUnits = $completedMaterialsCount + $forumProgressFraction;

        $progressPercent = $totalProgressUnits > 0
            ? (int) round($completedProgressUnits / $totalProgressUnits * 100)
            : 0;

        $sessionProgressService->upsert($activeSession->id, auth()->id(), $progressPercent);

        $nextMaterial = $activeSession->materials->first(fn ($material) => ! $completedMaterialIds->contains($material->id))
            ?? $activeSession->materials->first();

        $chips = $activeSession->materials->map(fn ($material) => [
            'key' => 'material:'.$material->id,
            'label' => $material->title,
            'completed' => $completedMaterialIds->contains($material->id),
            'type' => 'material',
            'id' => (string) $material->id,
        ])->values()->all();

        $chips[] = ['key' => 'assessment', 'label' => 'Assessment', 'completed' => false, 'type' => 'assessment', 'id' => null];
        $chips[] = ['key' => 'forum', 'label' => 'Forum', 'completed' => $forumCompleted, 'type' => 'forum', 'id' => null];

        $showVideoConferences = $activeSession->delivery_mode === DeliveryMode::VirtualClass
            && $activeSession->videoConferences->isNotEmpty();

        $openedVideoConferenceIds = $showVideoConferences
            ? $participationService->get([
                'user_id' => auth()->id(),
            ])->whereIn('video_conference_id', $activeSession->videoConferences->pluck('id'))->pluck('video_conference_id')
            : collect();

        $activeMaterial = $this->activeMaterialId
            ? $activeSession->materials->firstWhere('id', $this->activeMaterialId)
            : null;

        $activeItem = match ($this->activeCategory) {
            'assessment' => $activeSession->assessments->first(),
            'forum' => $activeSession->forums->first(),
            default => $activeMaterial ?? $nextMaterial,
        };

        $activeChipKey = match (true) {
            $this->activeCategory === 'material' && $activeItem !== null => 'material:'.$activeItem->id,
            $activeItem !== null => $this->activeCategory,
            default => $chips[0]['key'] ?? $this->activeCategory,
        };

        $materialPayloads = $activeSession->materials->mapWithKeys(
            fn ($material) => [(string) $material->id => $this->toPreviewPayload($material)]
        )->all();

        $forumTotalPosts = 0;
        $forumThreadPreviews = [];
        $forumPagination = null;

        $this->activeForumId = $forum?->id;

        if ($forum) {
            $totals = $forumThreadService->totalPostsForForum($forum->id);
            $forumTotalPosts = $totals['threads'] + $totals['comments'];

            $forumThreads = $forumThreadService->paginateForForum($forum->id, $this->forumPerPage, $this->forumPage, ['user', 'user.roles']);
            $forumThreadPreviews = $this->toForumThreadPreviews($forumThreads->items());
            $forumPagination = [
                'total' => $forumThreads->total(),
                'currentPage' => $forumThreads->currentPage(),
                'lastPage' => $forumThreads->lastPage(),
                'onFirstPage' => $forumThreads->onFirstPage(),
                'hasMorePages' => $forumThreads->hasMorePages(),
            ];
        }

        return [
            'activeSession' => $activeSession,
            'activeCategory' => $this->activeCategory,
            'completedMaterialIds' => $completedMaterialIds,
            'progressPercent' => $progressPercent,
            'chips' => $chips,
            'activeChipKey' => $activeChipKey,
            'activeItem' => $activeItem,
            'materialPayloads' => $materialPayloads,
            'openedVideoConferenceIds' => $openedVideoConferenceIds,
            'showVideoConferences' => $showVideoConferences,
            'videoConferenceWindowOpen' => $activeSession->isOngoing(),
            'forumWindowOpen' => $activeSession->isOngoing(),
            'forumTotalPosts' => $forumTotalPosts,
            'forumMyPostsCount' => $forumMyPostsCount,
            'forumRequiredPosts' => $requiredForumPosts,
            'forumThreadPreviews' => $forumThreadPreviews,
            'canCreateForumThread' => auth()->user()->can('forum.create'),
            'forumPagination' => $forumPagination,
        ];
    }

    /**
     * @param  array<int, ForumThread>  $threads
     * @return array<int, array{id: string, title: string, commentsCount: int, createdAtLabel: string, userName: string, userInitial: string, userAvatarUrl: string|null, roleLabel: string|null}>
     */
    private function toForumThreadPreviews(array $threads): array
    {
        $rows = [];

        foreach ($threads as $thread) {
            /** @var Role|null $role */
            $role = $thread->user->roles->first();

            $rows[] = [
                'id' => $thread->id,
                'title' => $thread->title,
                'commentsCount' => $thread->comments_count,
                'createdAtLabel' => $thread->created_at_display->format('d M Y, H:i'),
                'userName' => $thread->user->name,
                'userInitial' => strtoupper(substr($thread->user->name ?: 'U', 0, 1)),
                'userAvatarUrl' => $thread->user->profile_photo_path,
                'roleLabel' => $role?->name,
            ];
        }

        return $rows;
    }
}
