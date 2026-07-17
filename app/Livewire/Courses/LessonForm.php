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
    public ?string $durationMinutes = null;

    public bool $isPublished = false;

    public function mount(CurrentSchool $currentSchool, ?Module $module = null, ?Lesson $lesson = null): void
    {
        abort_unless(auth()->user()->can('lessons.create') || auth()->user()->can('lessons.edit'), 403);

        $module ??= $lesson?->module;

        abort_if($module === null, 404);

        if (! $module->relationLoaded('course')) {
            $module->load('course');
        }

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

        if ($this->videoEmbedUrl && ! $this->isValidVideoUrl($this->videoEmbedUrl)) {
            $this->addError('videoEmbedUrl', 'The video embed URL must be a YouTube or Vimeo link.');

            return;
        }

        $durationMinutes = $this->durationMinutes ? (int) $this->durationMinutes : null;

        if ($this->lesson) {
            $lessonService->update($this->lesson->id, [
                'title' => $this->title,
                'content' => $this->content,
                'video_embed_url' => $this->videoEmbedUrl,
                'duration_minutes' => $durationMinutes,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('lesson-updated');
        } else {
            $lessonService->create([
                'module_id' => $this->module->id,
                'title' => $this->title,
                'content' => $this->content,
                'video_embed_url' => $this->videoEmbedUrl,
                'duration_minutes' => $durationMinutes,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('lesson-created');
        }

        return redirect()->route('courses.show', $this->module->course);
    }

    private function isValidVideoUrl(string $url): bool
    {
        $youtubePatterns = [
            'youtube\.com\/watch\?v=',
            'youtube\.com\/embed\/',
            'youtu\.be\/',
        ];

        $vimeoPatterns = [
            'vimeo\.com\/',
            'player\.vimeo\.com\/video\/',
        ];

        $allPatterns = array_merge($youtubePatterns, $vimeoPatterns);
        foreach ($allPatterns as $pattern) {
            if (preg_match("/$pattern/i", $url)) {
                return true;
            }
        }

        return false;
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
