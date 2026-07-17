<?php

namespace App\Livewire\Courses;

use App\Models\Lesson;
use App\Models\Module;
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

    public function mount(CurrentSchool $currentSchool, Lesson $lesson): void
    {
        $this->lesson = $lesson;
        $this->module = $lesson->module;

        if (! $this->module->relationLoaded('course')) {
            $this->module->load('course');
        }

        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($this->module->course->school_id === $schoolId, 403);

        abort_unless($lesson->is_published, 403);

        $this->loadProgress();
        $this->loadNavigation();
    }

    private function loadProgress(): void
    {
        $user = auth()->user();

        if ($user) {
            $this->isCompleted = $this->lesson->isCompletedBy($user);
        }

        $lessons = $this->module->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->get();

        $this->totalLessonsInModule = $lessons->count();

        if ($user) {
            $this->completedLessonsInModule = $lessons->filter(
                fn (Lesson $lesson) => $lesson->isCompletedBy($user)
            )->count();
        }

        $this->currentLessonIndex = $lessons->search(
            fn (Lesson $lesson) => $lesson->id === $this->lesson->id
        ) + 1;
    }

    private function loadNavigation(): void
    {
        $lessons = $this->module->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->get();

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

        $this->lesson->markCompleteFor(auth()->user());
        $this->isCompleted = true;
        $this->dispatch('lesson-marked-complete', lessonId: $this->lesson->id);
        $this->loadProgress();
    }

    public function render()
    {
        return view('livewire.courses.lesson-viewer', [
            'pageTitle' => $this->lesson->title,
            'course' => $this->module->course,
            'completionPercentage' => $this->totalLessonsInModule > 0
                ? round(($this->completedLessonsInModule / $this->totalLessonsInModule) * 100)
                : 0,
        ])->extends('layouts.app', ['topbarTitle' => 'Lesson'])
            ->section('app-content');
    }
}
