<?php

namespace App\Livewire\Courses;

use App\Enums\MaterialType;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Module;
use App\Services\LessonMaterialService;
use App\Services\LessonService;
use App\Services\ModuleService;
use App\Services\UserLessonService;
use App\Support\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class LessonViewer extends Component
{
    public Lesson $lesson;

    public Module $module;

    public bool $isCompleted = false;

    public int $totalLessonsInModule = 0;

    public int $completedLessonsInModule = 0;

    public int $currentLessonIndex = 0;

    public ?Lesson $previousLesson = null;

    public ?Lesson $nextLesson = null;

    public Collection $materials;

    public ?LessonMaterial $selectedMaterial = null;

    public int $accessedMaterialCount = 0;

    public int $totalMaterialCount = 0;

    public Collection $assignments;

    public function mount(
        CurrentSchool $currentSchool,
        Lesson $lesson,
        LessonService $lessonService,
        ModuleService $moduleService,
        UserLessonService $userLessonService,
        LessonMaterialService $materialService,
    ): void {
        $this->lesson = $lesson;
        $this->module = $moduleService->find($lesson->module_id, ['course']);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($this->module->course->school_id === $schoolId, 403);

        abort_unless($lesson->is_published, 403);

        abort_if(auth()->user()->can('lessons.edit'), 403);

        $this->loadMaterials($materialService);
        $this->loadProgress($lessonService, $userLessonService, $materialService);
        $this->loadNavigation($lessonService);
        $this->loadAssignments();
    }

    private function loadAssignments(): void
    {
        $query = $this->lesson->assignments()->where('is_published', true);

        if (auth()->check()) {
            $query->with(['submissions' => fn ($q) => $q->where('user_id', auth()->id())->latest('submitted_at')]);
        }

        $this->assignments = $query->get();
    }

    private function loadMaterials(LessonMaterialService $materialService): void
    {
        $this->materials = $this->lesson->materials()->active()->orderBy('order')->get();
        $this->totalMaterialCount = $this->materials->count();

        if ($this->materials->count() > 0) {
            $this->selectedMaterial = $this->materials->first();
        }
    }

    private function loadProgress(
        LessonService $lessonService,
        UserLessonService $userLessonService,
        LessonMaterialService $materialService,
    ): void {
        if (auth()->check()) {
            $user = auth()->user();
            $this->isCompleted = $userLessonService->isCompletedBy($this->lesson->id, $user);
            $this->accessedMaterialCount = $materialService->getAccessedMaterialCount($this->lesson->id, $user);
        }

        $lessons = $lessonService->getByModulePublished($this->module->id);

        $this->totalLessonsInModule = $lessons->count();

        if (auth()->check()) {
            $user = auth()->user();
            $this->completedLessonsInModule = $lessons->filter(
                fn (Lesson $lesson) => $userLessonService->isCompletedBy($lesson->id, $user)
            )->count();
        }

        $this->currentLessonIndex = $lessons->search(
            fn (Lesson $lesson) => $lesson->id === $this->lesson->id
        ) + 1;
    }

    private function loadNavigation(LessonService $lessonService): void
    {
        $lessons = $lessonService->getByModulePublished($this->module->id);

        $currentIndex = $lessons->search(fn (Lesson $lesson) => $lesson->id === $this->lesson->id);

        if ($currentIndex > 0) {
            $this->previousLesson = $lessons[$currentIndex - 1];
        }

        if ($currentIndex < $lessons->count() - 1) {
            $this->nextLesson = $lessons[$currentIndex + 1];
        }
    }

    public function selectMaterial(string $materialId): void
    {
        $this->selectedMaterial = $this->materials->firstWhere('id', $materialId);
    }

    public function markMaterialAsRead(LessonMaterialService $materialService): void
    {
        if (! $this->selectedMaterial || ! auth()->check()) {
            return;
        }

        $user = auth()->user();
        $materialService->markMaterialAsAccessed($this->selectedMaterial->id, $user);
        $this->accessedMaterialCount = $materialService->getAccessedMaterialCount($this->lesson->id, $user);
        $this->dispatch('material-marked-read', materialId: $this->selectedMaterial->id);
    }

    public function isMaterialAccessed(LessonMaterialService $materialService, LessonMaterial $material): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return $materialService->isMaterialAccessedBy($material->id, auth()->user());
    }

    #[On('mark-complete')]
    public function markComplete(): void
    {
        if (! auth()->check()) {
            redirect()->route('login');

            return;
        }

        $user = auth()->user();
        $userLessonService = app(UserLessonService::class);
        $userLessonService->markComplete($this->lesson->id, $user);
        $this->isCompleted = true;
        $this->dispatch('lesson-marked-complete', lessonId: $this->lesson->id);
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

    public function render()
    {
        return view('livewire.courses.lesson-viewer', [
            'pageTitle' => $this->lesson->title,
            'course' => $this->module->course,
            'module' => $this->module,
            'lesson' => $this->lesson,
            'isCompleted' => $this->isCompleted,
            'totalLessonsInModule' => $this->totalLessonsInModule,
            'completedLessonsInModule' => $this->completedLessonsInModule,
            'currentLessonIndex' => $this->currentLessonIndex,
            'previousLesson' => $this->previousLesson,
            'nextLesson' => $this->nextLesson,
            'completionPercentage' => $this->totalLessonsInModule > 0
                ? round(($this->completedLessonsInModule / $this->totalLessonsInModule) * 100)
                : 0,
            'materialProgress' => $this->totalMaterialCount > 0
                ? round(($this->accessedMaterialCount / $this->totalMaterialCount) * 100)
                : 0,
            'materialService' => app(LessonMaterialService::class),
            'MaterialType' => MaterialType::class,
            'assignments' => $this->assignments,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Lesson'])
            ->section('app-content');
    }
}
