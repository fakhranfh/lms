<?php

namespace App\Livewire\Courses;

use App\Models\Lesson;
use App\Models\Module;
use App\Services\LessonService;
use App\Support\CurrentSchool;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LessonForm extends Component
{
    public Module $module;

    public ?Lesson $lesson = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public string $content = '';

    #[Validate('nullable|url')]
    public string $videoEmbedUrl = '';

    #[Validate('nullable|integer|min:1|max:480')]
    public ?int $durationMinutes = null;

    public bool $isPublished = false;

    public function mount(Module $module, ?Lesson $lesson, CurrentSchool $currentSchool): void
    {
        abort_unless(auth()->user()->can('lessons.create') || auth()->user()->can('lessons.edit'), 403);
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless($module->course->school_id === $schoolId, 403);

        $this->module = $module;

        if ($lesson) {
            $this->lesson = $lesson;
            $this->title = $lesson->title;
            $this->content = $lesson->content ?? '';
            $this->videoEmbedUrl = $lesson->video_embed_url ?? '';
            $this->durationMinutes = $lesson->duration_minutes;
            $this->isPublished = $lesson->is_published;
        }
    }

    public function save(LessonService $lessonService)
    {
        $this->validate();

        if ($this->lesson) {
            $lessonService->update($this->lesson->id, [
                'title' => $this->title,
                'content' => $this->content,
                'video_embed_url' => $this->videoEmbedUrl,
                'duration_minutes' => $this->durationMinutes,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('lesson-updated');
        } else {
            $lessonService->create([
                'module_id' => $this->module->id,
                'title' => $this->title,
                'content' => $this->content,
                'video_embed_url' => $this->videoEmbedUrl,
                'duration_minutes' => $this->durationMinutes,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('lesson-created');
        }

        return redirect()->route('courses.show', $this->module->course);
    }

    public function render()
    {
        return view('livewire.courses.lesson-form', [
            'pageTitle' => $this->lesson ? 'Edit Lesson' : 'Create Lesson',
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->lesson ? 'Edit Lesson' : 'Create Lesson'])
            ->section('app-content');
    }
}
