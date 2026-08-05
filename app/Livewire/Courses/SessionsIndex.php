<?php

namespace App\Livewire\Courses;

use App\Enums\DeliveryMode;
use App\Enums\MaterialType;
use App\Enums\RoleName;
use App\Models\Course;
use App\Models\MediaLibraryItem;
use App\Models\Session;
use App\Services\CoursePersonService;
use App\Services\SessionMaterialCompletionService;
use App\Services\SessionService;
use App\Services\VideoConferenceParticipationService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SessionsIndex extends Component
{
    public Course $course;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $isStudent = false;

    /** @var array<string, bool> */
    public array $expandedSessions = [];

    public ?string $activeSessionId = null;

    public string $activeCategory = 'material';

    public ?string $activeMaterialId = null;

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
    public function markVideoConferenceOpened(string $videoConferenceId, VideoConferenceParticipationService $participationService): void
    {
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

    public function render(SessionService $sessionService, SessionMaterialCompletionService $completionService, VideoConferenceParticipationService $participationService, CoursePersonService $coursePersonService)
    {
        if (! $this->sessionsLoaded) {
            return view('livewire.courses.sessions-index-placeholder', [
                'course' => $this->course,
                'isStudent' => $this->isStudent,
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
        ];

        if ($this->isStudent) {
            $viewData = array_merge($viewData, $this->buildStudentViewData($sessions, $completionService, $participationService));
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
    private function buildStudentViewData(Collection $sessions, SessionMaterialCompletionService $completionService, VideoConferenceParticipationService $participationService): array
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
            ];
        }

        $this->activeSessionId = $activeSession->id;

        $completedMaterialIds = $completionService->completedMaterialIds($activeSession->id, auth()->id());

        $totalMaterials = $activeSession->materials->count();
        $progressPercent = $totalMaterials > 0
            ? (int) round($completedMaterialIds->intersect($activeSession->materials->pluck('id'))->count() / $totalMaterials * 100)
            : 0;

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
        $chips[] = ['key' => 'forum', 'label' => 'Forum', 'completed' => false, 'type' => 'forum', 'id' => null];

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
        ];
    }
}
