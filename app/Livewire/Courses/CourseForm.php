<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Services\CourseService;
use App\Support\CurrentSchool;
use Illuminate\Database\QueryException;
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
                $slug = $this->generateUniqueSlug($courseService, $schoolId);

                $courseService->update($this->course->id, [
                    'title' => $this->title,
                    'description' => $this->description,
                    'slug' => $slug,
                    'is_published' => $this->isPublished,
                ]);

                return redirect()->route('courses.show', $this->course);
            }

            $course = $this->createCourseWithRetry($courseService, $schoolId);

            return redirect()->route('courses.show', $course);
        } catch (\Throwable $exception) {
            $this->dispatch('course-form-error');

            throw $exception;
        }
    }

    /**
     * Create the course, retrying with the next slug candidate if a
     * concurrent submission (e.g. a double-click) already claimed the
     * slug this school just generated for the same title.
     */
    private function createCourseWithRetry(CourseService $courseService, string $schoolId, int $maxAttempts = 5): Course
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $slug = $this->generateUniqueSlug($courseService, $schoolId);

            try {
                return $courseService->create([
                    'school_id' => $schoolId,
                    'created_by' => auth()->id(),
                    'title' => $this->title,
                    'description' => $this->description,
                    'slug' => $slug,
                    'is_published' => $this->isPublished,
                ]);
            } catch (QueryException $exception) {
                $isDuplicateSlug = in_array($exception->getCode(), ['23000', '23505'], true);

                if (! $isDuplicateSlug || $attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to generate a unique course slug.');
    }

    private function generateUniqueSlug(CourseService $courseService, string $schoolId): string
    {
        $baseSlug = Str::slug($this->title);
        $slug = $baseSlug;
        $suffix = 1;

        while ($courseService->slugExists($slug, $schoolId, $this->course?->id)) {
            $suffix++;
            $slug = "{$baseSlug}-{$suffix}";
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.courses.course-form')
            ->extends('layouts.app', ['topbarTitle' => $this->course ? 'Edit Course' : 'Create Course'])
            ->section('app-content');
    }
}
