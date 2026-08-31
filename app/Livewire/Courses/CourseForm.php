<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\CourseService;
use App\Support\CurrentSchool;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CourseForm extends Component
{
    public ?Course $course = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public bool $isPublished = false;

    public function mount(?Course $course = null): void
    {
        if ($course) {
            abort_unless(
                auth()->user()->can('courses.edit') && $course->school_id === auth()->user()->school_id,
                403
            );

            $this->course = $course;
            $this->title = $course->title;
            $this->description = $course->description ?? '';
            $this->isPublished = $course->is_published;
        } else {
            abort_unless(auth()->user()->can('courses.create'), 403);
        }
    }

    public function save(CourseService $courseService, CurrentSchool $currentSchool)
    {
        try {
            $this->validate();

            $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;

            if (! $schoolId) {
                abort(403, 'This action requires a school context.');
            }

            if ($this->course) {
                $courseService->update($this->course->id, [
                    'title' => $this->title,
                    'description' => $this->description,
                    'is_published' => $this->isPublished,
                ]);

                return redirect()->route('courses.show', $this->course);
            }

            $course = $courseService->create([
                'school_id' => $schoolId,
                'created_by' => auth()->id(),
                'title' => $this->title,
                'description' => $this->description,
                'is_published' => $this->isPublished,
            ]);

            return redirect()->route('courses.show', $course);
        } catch (\Throwable $exception) {
            $this->dispatch('course-form-error');

            throw $exception;
        }
    }

    public function render()
    {
        return view('livewire.courses.course-form')
            ->extends('layouts.app', ['topbarTitle' => $this->course ? 'Edit Course' : 'Create Course'])
            ->section('app-content');
    }
}
