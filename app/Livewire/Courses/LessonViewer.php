<?php

namespace App\Livewire\Courses;

use App\Models\Lesson;
use App\Models\Module;
use App\Services\LessonService;
use App\Services\ModuleService;
use App\Services\UserLessonService;
use App\Support\CurrentSchool;
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

    public function mount(
        CurrentSchool $currentSchool,
        Lesson $lesson,
        LessonService $lessonService,
        ModuleService $moduleService,
        UserLessonService $userLessonService,
    ): void {
        $this->lesson = $lesson;
        $this->module = $moduleService->find($lesson->module_id, ['course']);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($this->module->course->school_id === $schoolId, 403);

        abort_unless($lesson->is_published, 403);

        $this->loadProgress($lessonService, $userLessonService);
        $this->loadNavigation($lessonService);
    }

    private function loadProgress(LessonService $lessonService, UserLessonService $userLessonService): void
    {
        $user = auth()->user();

        if ($user) {
            $this->isCompleted = $userLessonService->isCompletedBy($this->lesson->id, $user);
        }

        $lessons = $lessonService->getByModulePublished($this->module->id);

        $this->totalLessonsInModule = $lessons->count();

        if ($user) {
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

    #[On('mark-complete')]
    public function markComplete(): void
    {
        $user = auth()->user();

        if (! $user) {
            redirect()->route('login');

            return;
        }

        $userLessonService = app(UserLessonService::class);
        $userLessonService->markComplete($this->lesson->id, $user);
        $this->isCompleted = true;
        $this->dispatch('lesson-marked-complete', lessonId: $this->lesson->id);
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
        ])->extends('layouts.app', ['topbarTitle' => 'Lesson'])
            ->section('app-content');
    }
}
