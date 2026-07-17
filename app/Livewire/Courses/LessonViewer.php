<?php

namespace App\Livewire\Courses;

use App\Models\Lesson;
use App\Models\Module;
use App\Repositories\Lesson\LessonRepositoryInterface;
use App\Repositories\Module\ModuleRepositoryInterface;
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
        LessonRepositoryInterface $lessonRepository,
        ModuleRepositoryInterface $moduleRepository,
    ): void {
        $this->lesson = $lesson;
        $this->module = $moduleRepository->find($lesson->module_id, ['course']);

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($this->module->course->school_id === $schoolId, 403);

        abort_unless($lesson->is_published, 403);

        $this->loadProgress($lessonRepository);
        $this->loadNavigation($lessonRepository);
    }

    private function loadProgress(LessonRepositoryInterface $lessonRepository): void
    {
        $user = auth()->user();

        if ($user) {
            $this->isCompleted = $lessonRepository->isCompletedBy($this->lesson->id, $user->id);
        }

        $lessons = $lessonRepository->getByModulePublished($this->module->id);

        $this->totalLessonsInModule = $lessons->count();

        if ($user) {
            $this->completedLessonsInModule = $lessons->filter(
                fn (Lesson $lesson) => $lessonRepository->isCompletedBy($lesson->id, $user->id)
            )->count();
        }

        $this->currentLessonIndex = $lessons->search(
            fn (Lesson $lesson) => $lesson->id === $this->lesson->id
        ) + 1;
    }

    private function loadNavigation(LessonRepositoryInterface $lessonRepository): void
    {
        $lessons = $lessonRepository->getByModulePublished($this->module->id);

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
        if (! auth()->check()) {
            redirect()->route('login');

            return;
        }

        $lessonRepository = app(LessonRepositoryInterface::class);
        $lessonRepository->markComplete($this->lesson->id, auth()->user()->id);
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
