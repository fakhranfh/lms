<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Models\Module;
use App\Services\ModuleService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ModuleForm extends Component
{
    public Course $course;

    public ?Module $module = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public bool $isPublished = false;

    public function mount(Course $course, ?Module $module = null): void
    {
        abort_unless(auth()->user()->can('modules.create') || auth()->user()->can('modules.edit'), 403);
        abort_unless($course->school_id === auth()->user()->school_id, 403);

        $this->course = $course;

        if ($module) {
            $this->module = $module;
            $this->title = $module->title;
            $this->description = $module->description ?? '';
            $this->isPublished = $module->is_published;
        }
    }

    public function save(ModuleService $moduleService)
    {
        $this->validate();

        if ($this->module) {
            $moduleService->update($this->module->id, [
                'title' => $this->title,
                'description' => $this->description,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('module-updated');
        } else {
            $moduleService->create([
                'course_id' => $this->course->id,
                'title' => $this->title,
                'description' => $this->description,
                'is_published' => $this->isPublished,
            ]);

            $this->dispatch('module-created');
        }

        return redirect()->route('courses.show', $this->course);
    }

    public function render()
    {
        return view('livewire.courses.module-form', [
            'pageTitle' => $this->module ? 'Edit Module' : 'Create Module',
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->module ? 'Edit Module' : 'Create Module'])
            ->section('app-content');
    }
}
