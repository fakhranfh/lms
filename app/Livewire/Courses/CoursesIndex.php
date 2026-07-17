<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Support\CurrentSchool;
use Livewire\Component;
use Livewire\WithPagination;

class CoursesIndex extends Component
{
    use WithPagination;

    public ?string $search = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('courses.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(CurrentSchool $currentSchool)
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        $query = Course::where('school_id', $schoolId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $courses = $query->with(['creator', 'modules'])->latest()->paginate(10);

        return view('livewire.courses.courses-index', [
            'courses' => $courses,
        ])
            ->extends('layouts.app', ['topbarTitle' => 'Courses'])
            ->section('app-content');
    }
}
