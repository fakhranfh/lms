<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CourseForm extends Component
{
    public ?Course $course = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('nullable|string|max:255')]
    public string $slug = '';

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
            $this->slug = $course->slug ?? '';
            $this->isPublished = $course->is_published;
        } else {
            abort_unless(auth()->user()->can('courses.create'), 403);
        }
    }

    public function updated(string $property): void
    {
        if ($property === 'title' && ! $this->slug) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function save(CourseService $courseService)
    {
        $this->validate();

        $schoolId = auth()->user()->school_id;

        if ($courseService->slugExists($this->slug, $schoolId, $this->course?->id)) {
            $this->addError('slug', __('Slug already exists for this school.'));

            return null;
        }

        if ($this->course) {
            $courseService->update($this->course->id, [
                'title' => $this->title,
                'description' => $this->description,
                'slug' => $this->slug,
                'is_published' => $this->isPublished,
            ]);

            return redirect()->route('courses.show', $this->course);
        }

        $course = $courseService->create([
            'school_id' => $schoolId,
            'created_by' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'is_published' => $this->isPublished,
        ]);

        return redirect()->route('courses.show', $course);
    }

    public function render()
    {
        return view('livewire.courses.course-form')
            ->extends('layouts.app', ['topbarTitle' => $this->course ? 'Edit Course' : 'Create Course'])
            ->section('app-content');
    }
}
